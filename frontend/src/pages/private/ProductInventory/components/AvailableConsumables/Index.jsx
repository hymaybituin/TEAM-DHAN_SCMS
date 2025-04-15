import { useEffect, useState } from "react";
import {
  Spin,
  Row,
  Col,
  Button,
  Drawer,
  Table,
  Dropdown,
  Tag,
  Typography,
  Image,
  Space,
  Popover,
  Card,
  Segmented,
} from "antd";
import {
  MoreOutlined,
  ArrowDownOutlined,
  EnvironmentOutlined,
  WarningOutlined,
} from "@ant-design/icons";
import dayjs from "dayjs";

import ErrorContent from "../../../../../components/common/ErrorContent";

import http from "../../../../../services/httpService";
import { getColumnSearchProps } from "../../../../../helpers/TableFilterProps";
import { formatWithComma } from "../../../../../helpers/numbers";

import Barcode from "react-barcode";

const { Text } = Typography;

function AvailableConsumables({ gIs, status }) {
  const [groupedItems, setGroupItems] = useState([]);
  const [selectedGroupItem, setSelectedGroupItem] = useState(null);

  const [isItemsModalOpen, setIsItemsModalOpen] = useState(null);

  const [isContentLoading, setIsContentLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState(null);

  const getGroupItems = async () => {
    //const { data } = await http.get("/api/getAllProducts");
    //console.log(data);
    //setProducts(data);
    let groupedItems = [...gIs];
    if (status !== "ALL") {
      groupedItems = gIs.filter((gI) => gI.status === status);
    }

    setGroupItems(groupedItems);
  };

  useEffect(() => {
    const fetchGroupedItems = async () => {
      try {
        setIsContentLoading(true);
        await getGroupItems();
      } catch (error) {
        setErrorMsg(error.message || "Something went wrong!");
      } finally {
        setIsContentLoading(false);
      }
    };

    fetchGroupedItems();
  }, []);

  if (errorMsg) {
    return <ErrorContent errorMessage={errorMsg} />;
  }

  const toggleModalItems = () => {
    setIsItemsModalOpen(!isItemsModalOpen);
  };

  const tableColumns = [
    {
      title: "Lot Number",
      dataIndex: "lot_number",
      render: (text) => text || "N/A",
    },
    {
      title: "Quantity",
      dataIndex: "quantity",
    },
    {
      title: "Expiration Date",
      dataIndex: "expiration_date",
      render: (text) => text || "N/A",
    },
    {
      title: "Expires In",
      dataIndex: "remaining_time",
      render: (text) => text || "N/A",
    },
    {
      title: "Action",
      width: 50,
      render: (_, record) => {
        const menuItems = [{ key: "Update", label: "Update" }];

        const handleMenuClick = ({ key }) => {
          if (key === "Update") {
            // setSelectedProduct(record);
            // toggleFormUpdateProductOpen();
          } else if (key === "Delete") {
            // Modal.confirm({
            //   title: "Delete Product",
            //   content: "Are you sure you want to delete this product?",
            //   onOk: async () => {
            //     handleDeleteProduct(record);
            //   },
            // });
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

  return (
    <>
      <Spin spinning={isContentLoading} tip="loading ...">
        <Table
          rowKey="lot_number"
          columns={tableColumns}
          dataSource={groupedItems}
          expandable={{
            expandedRowRender: (record) => {
              const columns = [
                {
                  title: "Barcode",
                  dataIndex: "barcode",
                  render: (text) => (
                    <Barcode value={text} height={20} displayValue={true} />
                  ),
                },
                {
                  title: "Action",
                  render: () => <Button type="primary">Print</Button>,
                  width: 100,
                },
              ];

              return (
                <Card>
                  <Table
                    columns={columns}
                    dataSource={record.barcodes.map((item) => ({
                      barcode: item,
                    }))}
                    pagination={false} // To display all data without pagination
                    rowKey="barcode"
                  />
                </Card>
              );
            },
          }}
        />
      </Spin>
    </>
  );
}

export default AvailableConsumables;
