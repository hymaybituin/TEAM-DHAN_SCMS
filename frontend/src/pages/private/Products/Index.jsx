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

import ErrorContent from "../../../components/common/ErrorContent";

import http from "../../../services/httpService";
import { getColumnSearchProps } from "../../../helpers/TableFilterProps";
import { formatWithComma } from "../../../helpers/numbers";

const { Text } = Typography;

function Products() {
  const [products, setProducts] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [selectedProductType, setSelectedProductType] = useState("Consumables");

  const [isProductInventoryModalOpen, setIsProductInventoryModalOpen] =
    useState(null);

  const [isContentLoading, setIsContentLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState(null);

  const getProducts = async () => {
    const { data } = await http.get("/api/getAllProducts");
    console.log(data);
    setProducts(data);
  };

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        setIsContentLoading(true);
        await getProducts();
      } catch (error) {
        setErrorMsg(error.message || "Something went wrong!");
      } finally {
        setIsContentLoading(false);
      }
    };

    fetchProducts();
  }, []);

  if (errorMsg) {
    return <ErrorContent errorMessage={errorMsg} />;
  }

  const toggleProductInventoryModelOpen = () => {
    setIsProductInventoryModalOpen(!isProductInventoryModalOpen);
    // setIsFormCreateProductOpen(!isFormCreateProductOpen);
  };

  const tableColumns = [
    {
      title: "",
      dataIndex: "image_url",
      render: (text) => {
        return <Image width={50} src={"https://placehold.co/50x50"} />;
      },
      width: 100,
    },
    {
      title: "Name",
      dataIndex: "name",
      ...getColumnSearchProps("name"),
      render: (_, record) => {
        return (
          <>
            <div>
              {record.name} &mdash; {record.model}{" "}
            </div>
            <div>
              <Text type="secondary">
                <EnvironmentOutlined /> {record.warehouse.name} -{" "}
                {record.location.name}
              </Text>
            </div>
          </>
        );
      },
    },
    {
      title: "Tags",
      render: (_, record) => {
        return record.tags.length !== 0 ? (
          <Space>
            {record.tags.map((tag) => (
              <Tag key={tag.id}>{tag.name}</Tag>
            ))}
          </Space>
        ) : (
          "-"
        );
      },
      filters: [
        {
          text: "CONTROL",
          value: "CONTROL",
        },
      ],
      onFilter: (value, record) =>
        record.tags
          .map((tag) => tag.name)
          .join(",")
          .includes(value),
      filterSearch: true,
      width: 300,
    },
    {
      title: "Available Qty.",
      dataIndex: "available_quantity",
      width: 150,
      render: (text, record) => {
        const { quantity_level, minimum_quantity } = record;
        if (
          quantity_level === "Below Minimum" ||
          quantity_level === "No Stock"
        ) {
          return (
            <Popover
              content={<>Minimum Quantity: {minimum_quantity}</>}
              title={quantity_level}
            >
              <span style={{ color: "#f50" }}>
                {quantity_level === "Below Minimum" ? (
                  <strong>
                    <ArrowDownOutlined />
                  </strong>
                ) : (
                  <strong>
                    <WarningOutlined />
                  </strong>
                )}
              </span>{" "}
              {text}
            </Popover>
          );
        }
        return <span>{text}</span>;
      },
      filters: [
        {
          text: "No Stock",
          value: "No Stock",
        },
        {
          text: "Below Minimum",
          value: "Below Minimum",
        },
        {
          text: "Above Minimum",
          value: "Above Minimum",
        },
      ],
      onFilter: (value, record) => record.quantity_level === value,
    },
    {
      title: "Selling Price",
      dataIndex: "default_selling_price",
      render: (text, record) => (
        <Popover
          content={
            <>
              <div>
                Supplier Price: &#8369;{formatWithComma(record.supplier_price)}
              </div>
              <div>Profit Margin: {record.profit_margin}</div>
            </>
          }
          title="Selling Price"
        >
          &#8369;{formatWithComma(text)}
        </Popover>
      ),
      width: 150,
    },
    {
      title: "Action",
      width: 50,
      render: (_, record) => {
        const menuItems = [
          { key: "Update", label: "Update" },
          {
            type: "divider",
          },
          { key: "Delete", label: "Delete", danger: true },
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

  const consumablesColumns = [
    {
      title: "Viable",
      render: (_, record) => {
        const { incoming_stocks, available_quantity } = record;

        if (available_quantity === 0) {
          return "-";
        }

        const validItems = incoming_stocks.filter(
          (item) => item.expiration_date
        );

        if (validItems.length === 0) {
          return "N/A";
        }

        const viableProducts = validItems.filter(
          (item) => item.status === "VIABLE"
        );

        const viableProductsCount = viableProducts.reduce((acc, item) => {
          acc += item.quantity;
          return acc;
        }, 0);

        return viableProductsCount;
      },
      width: 100,
    },
    {
      title: "Expiring",
      render: (_, record) => {
        const { incoming_stocks, available_quantity } = record;

        if (available_quantity === 0) {
          return "-";
        }

        const validItems = incoming_stocks.filter(
          (item) => item.expiration_date
        );

        if (validItems.length === 0) {
          return "N/A";
        }

        const expiringProducts = validItems.filter(
          (item) => item.status === "EXPIRING"
        );

        const expiringProductsCount = expiringProducts.reduce((acc, item) => {
          acc += item.quantity;
          return acc;
        }, 0);

        return expiringProductsCount;
      },
      width: 100,
    },
    {
      title: "Expired",
      render: (_, record) => {
        const { incoming_stocks, available_quantity } = record;

        if (available_quantity === 0) {
          return "-";
        }

        const validItems = incoming_stocks.filter(
          (item) => item.expiration_date
        );

        if (validItems.length === 0) {
          return "N/A";
        }

        const expiredProducts = validItems.filter(
          (item) => item.status === "EXPIRED"
        );

        const expiredProductsCount = expiredProducts.reduce((acc, item) => {
          acc += item.quantity;
          return acc;
        }, 0);

        return expiredProductsCount;
      },
      width: 100,
    },
  ];

  if (selectedProductType === "Consumables") {
    tableColumns.splice(3, 0, ...consumablesColumns);
  }

  const filteredProducts = products.filter((product) => {
    if (selectedProductType === "Consumables") {
      return !product.is_machine;
    } else {
      return product.is_machine;
    }
  });

  return (
    <>
      <Spin spinning={isContentLoading} tip="loading ...">
        <Row type="flex" justify="space-between" style={{ marginBottom: 16 }}>
          <Col>
            <Segmented
              options={["Consumables", "Machine"]}
              onChange={(value) => {
                setSelectedProductType(value); // string
              }}
            />
          </Col>
          <Col>
            <Button type="primary" onClick={toggleProductInventoryModelOpen}>
              Create Product
            </Button>
          </Col>
        </Row>
        <Table
          rowKey="id"
          columns={tableColumns}
          dataSource={filteredProducts}
          expandable={{
            expandedRowRender: (record) => {
              const columns = [
                {
                  title: "Field",
                  dataIndex: "field",
                  key: "field",
                  width: 200,
                },
                {
                  title: "Value",
                  dataIndex: "value",
                  key: "value",
                },
              ];

              let tableData = [
                { field: "Product Name", value: record.name },
                { field: "SKU", value: record.sku },
                { field: "Model", value: record.model },
                { field: "Description", value: record.description },
                { field: "Unit", value: record.product_unit.name },
                {
                  field: "Available Quantity",
                  value: record.available_quantity,
                },
                {
                  field: "Minimum Quantity",
                  value: record.minimum_quantity,
                },
                {
                  field: "Quantity Level",
                  value: record.quantity_level,
                },
                { field: "Supplier Name", value: record.supplier.name },
                {
                  field: "Supplier Price",
                  value: `₱${record.supplier_price}`,
                },
                {
                  field: "Profit Margin (%)",
                  value: record.profit_margin,
                },
                {
                  field: "Default Selling Price",
                  value: `₱${record.default_selling_price}`,
                },
                { field: "Location", value: record.location.name },
                { field: "Warehouse", value: record.warehouse.name },

                {
                  field: "Tags",
                  value: record.tags.map((tag) => tag.name).join(", "),
                },
                { field: "Status", value: record.status.name },
                {
                  field: "Created At",
                  value: dayjs(record.created_at).format(
                    "MMMM, DD YYYY HH:mm A"
                  ),
                },
                { field: "Created By", value: record.creator.full_name },
                {
                  field: "Updated At",
                  value: dayjs(record.updated_at).format(
                    "MMMM, DD YYYY HH:mm A"
                  ),
                },
                { field: "Updated By", value: record.updater.full_name },
                // {
                //   key: '16',
                //   field: 'Incoming Stock',
                //   value: record.incoming_stocks
                //     .map(stock => `Lot: ${stock.lot_number}, Qty: ${stock.quantity}, Status: ${stock.status}`)
                //     .join('; '),
                // },
              ];

              tableData = tableData.map((i, index) => ({ ...i, key: index }));

              return (
                <Card>
                  <Table
                    columns={columns}
                    dataSource={tableData}
                    pagination={false} // To display all data without pagination
                    rowKey="key"
                  />
                </Card>
              );
            },
          }}
        />
      </Spin>

      <Drawer
        title="Product Inventory"
        open={isProductInventoryModalOpen}
        destroyOnClose
        width={960}
        onClose={toggleProductInventoryModelOpen}
      >
        gg
      </Drawer>
    </>
  );
}

export default Products;
