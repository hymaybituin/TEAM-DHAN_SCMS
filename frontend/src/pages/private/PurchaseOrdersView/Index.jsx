import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import {
  Row,
  Col,
  Button,
  Table,
  Typography,
  Descriptions,
  Empty,
  Skeleton,
  Tag,
} from "antd";

import ErrorContent from "../../../components/common/ErrorContent";

import http from "../../../services/httpService";
import { formatWithComma } from "../../../helpers/numbers";

const { Title, Text } = Typography;

function PurchaseOrdersView() {
  const [purchaseOrder, setPurcaseOrder] = useState(null);

  const [isContentLoading, setIsContentLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState(null);

  const { purchaseOrderId } = useParams();

  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsContentLoading(true);

        const { data: purchaseOrder } = await http.get(
          `/api/purchaseOrders/${purchaseOrderId}`
        );

        setPurcaseOrder(purchaseOrder.purchase_order);
      } catch (error) {
        setErrorMsg(error.message);
      } finally {
        setIsContentLoading(false);
      }
    };

    fetchData();
  }, []);

  if (errorMsg) {
    return <ErrorContent errorMessage={errorMsg} />;
  }

  if (isContentLoading) {
    return <Skeleton />;
  }

  if (!purchaseOrder) {
    return <Empty />;
  }

  const tableColumns = [
    {
      title: "Name",
      render: (_, record) => {
        return record.product.name;
      },
    },
    {
      title: "Unit",
      width: 100,
      render: (_, record) => {
        return record.product.product_unit.name;
      },
    },
    {
      title: "Quantity",
      dataIndex: "quantity",
      width: 100,
      render: (text) => formatWithComma(text),
    },
    {
      title: "Price",
      dataIndex: "unit_price",
      width: 100,
      render: (text) => formatWithComma(text),
    },
    {
      title: "Amount",
      dataIndex: "total_price",
      width: 100,
      render: (text) => formatWithComma(text),
    },
  ];

  const { ponumber, supplier, items, total_amount, status_id, status } =
    purchaseOrder;

  let statusColor = "orange";
  // if (status_id === 5) {
  //   statusColor = "green";
  // } else if (status_id === 6) {
  //   statusColor = "blue";
  // } else if (status_id === 7) {
  //   statusColor = "purple";
  // } else if (status_id === 8) {
  //   statusColor = "red";
  // }

  const handlePrint = () => {
    window.print();
  };

  return (
    <>
      <Button onClick={handlePrint} type="primary" style={{ marginBottom: 16 }}>
        Print
      </Button>
      <div id="printArea">
        <Row type="flex" justify="space-between" style={{ marginBottom: 16 }}>
          <Col>
            <Title level={5} style={{ margin: 0 }}>
              Purchase Order Number: #{ponumber}
            </Title>
          </Col>
          <Col>
            <Tag color={statusColor}>{status.name}</Tag>
          </Col>
        </Row>
        {supplier && (
          <div style={{ marginBottom: 16 }}>
            <Title level={5} style={{ marginBottom: 0 }}>
              Supplier: {supplier.name}
            </Title>
            <div>
              <Text type="secondary">{supplier.contact_info}</Text>
            </div>
          </div>
        )}
        <Table
          columns={tableColumns}
          dataSource={items}
          rowKey="product_id"
          pagination={false}
        />
        <Row type="flex" justify="space-between" style={{ marginTop: 16 }}>
          <Col></Col>
          <Col>
            <Descriptions
              bordered
              column={1}
              items={[
                {
                  label: "Subtotal:",
                  children: formatWithComma(total_amount),
                },
                {
                  label: "Total:",
                  children: formatWithComma(total_amount),
                },
              ]}
              style={{ marginBottom: 16 }}
            />
          </Col>
        </Row>
      </div>
    </>
  );
}

export default PurchaseOrdersView;
