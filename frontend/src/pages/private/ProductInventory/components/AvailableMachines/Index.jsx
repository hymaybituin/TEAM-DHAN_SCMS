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

import Calibrations from "./components/Calibrations/Index";

const { Text } = Typography;

function AvailableMachines({ gIs, status }) {
  const [groupedItems, setGroupItems] = useState([]);
  const [selectedGroupItem, setSelectedGroupItem] = useState(null);

  const [isItemsModalOpen, setIsItemsModalOpen] = useState(null);

  const [isContentLoading, setIsContentLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState(null);

  const getGroupItems = async () => {
    //const { data } = await http.get("/api/getAllProducts");
    //console.log(data);
    //setProducts(data);
    setGroupItems(gIs);
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
      title: "Serial",
      dataIndex: "serial_number",
    },
    {
      title: "For Calibration",
      render: (_, record) => (record.for_calibration ? "Yes" : "No"),
    },
    {
      title: "For Maintenance",
      render: (_, record) => (record.for_maintenance ? "Yes" : "No"),
    },
    {
      title: "Status",
      dataIndex: "status",
    },
    {
      title: "Action",
      width: 50,
      render: (_, record) => {
        const menuItems = [
          { key: "View", label: "View" },
          { key: "Update", label: "Update" },
        ];

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
          rowKey="id"
          columns={tableColumns}
          dataSource={groupedItems}
          expandable={{
            expandedRowRender: (record) => {
              //   const columns = [
              //     {
              //       title: "Field",
              //       dataIndex: "field",
              //       key: "field",
              //       width: 200,
              //     },
              //     {
              //       title: "Value",
              //       dataIndex: "value",
              //       key: "value",
              //     },
              //   ];
              return (
                <Card>
                  {/* <Table
                    columns={columns}
                    dataSource={[]}
                    pagination={false} // To display all data without pagination
                    rowKey="key"
                  /> */}
                  <Calibrations />
                </Card>
              );
            },
          }}
        />
      </Spin>
    </>
  );
}

export default AvailableMachines;
