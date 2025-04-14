import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  Spin,
  Row,
  Col,
  Button,
  Table,
  Dropdown,
  Tabs,
  Badge,
  Typography,
  Tag,
  App,
} from "antd";
import { MoreOutlined } from "@ant-design/icons";

import ErrorContent from "../../../components/common/ErrorContent";

import http from "../../../services/httpService";

import { getColumnSearchProps } from "../../../helpers/TableFilterProps";
import { formatWithComma } from "../../../helpers/numbers";

import useDataStore from "../../../store/DataStore";

const { Text } = Typography;

function PurchaseOrders() {
  const [purchaseOrders, setPurchaseOrders] = useState([]);

  const [isContentLoading, setIsContentLoading] = useState(false);
  const [error, setError] = useState(null);

  const navigate = useNavigate();
  const { statuses } = useDataStore();
  const { modal } = App.useApp();

  const getPurchaseOrders = async () => {
    const { data } = await http.get("/api/purchaseOrders");
    setPurchaseOrders(data);
  };

  useEffect(() => {
    const fetchPurchaseOrders = async () => {
      try {
        setIsContentLoading(true);
        await getPurchaseOrders();
      } catch (error) {
        setError(error);
      } finally {
        setIsContentLoading(false);
      }
    };

    fetchPurchaseOrders();
  }, []);

  if (error) {
    return <ErrorContent />;
  }

  const handleUpdatePurchaseOrder = async (purchaseOrder, newStatusId) => {
    try {
      setIsContentLoading(true);
      await http.put(`/api/purchaseOrders/${purchaseOrder.id}`, {
        status_id: Number(newStatusId),
      });

      if (Number(newStatusId) === 6) {
        const { data } = await http.get(
          `/api/purchaseOrders/${purchaseOrder.id}`
        );

        const { purchase_order_items: purchaseOrderItems } = data;

        const consumableItems = purchaseOrderItems.filter(
          ({ product }) => product.product_category_id == 1
        );
        const equipmentItems = purchaseOrderItems
          .filter(({ product }) => product.product_category_id == 2)
          .flatMap((equipment) =>
            Array.from({ length: equipment.quantity }, () => {
              return {
                ...equipment,
                quantity: 1,
              };
            })
          );

        const forInsertProductItemConsumables = consumableItems.map(
          (consumableItem) => {
            return {
              product_id: consumableItem.product_id,
              purchase_order_id: purchaseOrder.id,
              batch_number: null,
              expiry_date: null,
              other_details: null,
              barcode: null,
              location_id: 1,
              warehouse_id: 1,
              status_id: 3,
            };
          }
        );

        const forInsertProductItemEquipments = equipmentItems.map(
          (equipmentItem) => {
            return {
              product_id: equipmentItem.product_id,
              purchase_order_id: purchaseOrder.id,
              serial_number: null,
              model_number: null,
              maintenance_interval_in_month: 0,
              other_details: null,
              barcode: null,
              location_id: 1,
              warehouse_id: 1,
              status_id: 3,
            };
          }
        );

        await http.post(
          "/api/productItemConsumablesNew",
          forInsertProductItemConsumables
        );

        await http.post(
          "/api/productItemEquipmentsNew",
          forInsertProductItemEquipments
        );
      }

      await getPurchaseOrders();
    } catch (error) {
      setError(error);
    } finally {
      setIsContentLoading(false);
    }
  };

  const tableColumns = [
    {
      title: "Purchase Order No.",
      dataIndex: "id",
      ...getColumnSearchProps("id"),
      render: (_, record) => {
        const { id, supplier, notes } = record;

        return (
          <div>
            <div>
              <Text strong>MG{String(id).padStart(4, "0")}</Text>
            </div>
            <div>{supplier.name}</div>
            <div>
              <Text type="secondary">{supplier.address}</Text>
            </div>
            <div>
              {notes && (
                <Text type="secondary" italic>
                  {notes}
                </Text>
              )}
            </div>
          </div>
        );
      },
    },
    {
      title: "Amount",
      dataIndex: "total_amount",
      width: 100,
      render: (text) => formatWithComma(text),
    },
    {
      title: "Status",
      dataIndex: "status_id",
      width: 100,
      render: (status_id) => {
        let color = "orange";
        if (status_id === 5) {
          color = "green";
        } else if (status_id === 6) {
          color = "blue";
        } else if (status_id === 7) {
          color = "purple";
        } else if (status_id === 8) {
          color = "red";
        }
        return <Tag color={color}>{statuses[status_id]}</Tag>;
      },
    },
    {
      title: "Action",
      width: 50,
      render: (_, record) => {
        const menuItems = [
          { key: "View", label: "View Details" },
          {
            type: "divider",
          },
          { key: 8, label: statuses[8], danger: true },
        ];

        if (record.status_id === 4) {
          menuItems.unshift({ key: 5, label: statuses[5] });
        }

        if (record.status_id === 5) {
          menuItems.unshift({ key: 10, label: statuses[10] });
        }

        if (record.status_id === 10) {
          menuItems.unshift({ key: 6, label: statuses[6] });
          menuItems.pop();
          menuItems.pop();
        }

        if (record.status_id === 6) {
          menuItems.unshift({ key: 7, label: statuses[7] });
          menuItems.pop();
          menuItems.pop();
        }

        if (record.status_id === 7) {
          menuItems.unshift({ key: 8, label: statuses[8] });
          menuItems.pop();
          menuItems.pop();
        }

        if (record.status_id === 7 || record.status_id === 8) {
          menuItems.pop();
          menuItems.pop();
        }

        const handleMenuClick = ({ key }) => {
          if (key === "View") {
            navigate(`/purchaseOrders/${record.id}`);
          } else {
            modal.confirm({
              title: `${statuses[key]} Purchase Order`,
              content: `Are you sure you want to ${statuses[
                key
              ].toLowerCase()} this purchase order?`,
              onOk: async () => {
                handleUpdatePurchaseOrder(record, key);
              },
            });
          }
        };

        return (
          <Dropdown
            menu={{ items: menuItems, onClick: handleMenuClick }}
            trigger={["click"]}
            placement="bottomRight"
          >
            <Button shape="circle" onClick={(e) => e.stopPropagation()}>
              <MoreOutlined />
            </Button>
          </Dropdown>
        );
      },
    },
  ];

  const pendingPOs = purchaseOrders.filter((po) => po.status_id === 4);
  const processingPOs = purchaseOrders.filter((po) => po.status_id === 10);
  const approvedPOs = purchaseOrders.filter((po) => po.status_id === 5);
  const fulfilledPOs = purchaseOrders.filter((po) => po.status_id === 6);
  const cancelledPos = purchaseOrders.filter((po) => po.status_id === 8);

  const tabItems = [
    {
      key: "1",
      label: (
        <>
          Pending{" "}
          {pendingPOs.length > 0 && (
            <Badge count={pendingPOs.length} color="gold" />
          )}
        </>
      ),
      children: (
        <Table columns={tableColumns} dataSource={pendingPOs} rowKey="id" />
      ),
    },
    {
      key: "2",
      label: (
        <>
          Approved{" "}
          {approvedPOs.length > 0 && (
            <Badge count={approvedPOs.length} color="green" />
          )}
        </>
      ),
      children: (
        <Table columns={tableColumns} dataSource={approvedPOs} rowKey="id" />
      ),
    },
    {
      key: "22",
      label: (
        <>
          Processing{" "}
          {processingPOs.length > 0 && (
            <Badge count={processingPOs.length} color="green" />
          )}
        </>
      ),
      children: (
        <Table columns={tableColumns} dataSource={processingPOs} rowKey="id" />
      ),
    },
    {
      key: "3",
      label: (
        <>
          Fulfilled{" "}
          {fulfilledPOs.length > 0 && (
            <Badge count={fulfilledPOs.length} color="blue" />
          )}
        </>
      ),
      children: (
        <Table columns={tableColumns} dataSource={fulfilledPOs} rowKey="id" />
      ),
    },
    {
      key: "4",
      label: "Cancelled",
      children: (
        <Table columns={tableColumns} dataSource={cancelledPos} rowKey="id" />
      ),
    },
    {
      key: "5",
      label: "All Purchase Orders",
      children: (
        <Table columns={tableColumns} dataSource={purchaseOrders} rowKey="id" />
      ),
    },
  ];

  return (
    <>
      <Spin spinning={isContentLoading} tip="loading ...">
        <Row type="flex" justify="space-between" style={{ marginBottom: 16 }}>
          <Col></Col>
          <Col>
            <Link to="/purchaseOrders/create">
              <Button type="primary">Create Purchase Order</Button>
            </Link>
          </Col>
        </Row>
        <Tabs defaultActiveKey="1" items={tabItems} />
      </Spin>
    </>
  );
}

export default PurchaseOrders;
