import { Routes, Route, Navigate } from "react-router-dom";

import AuthWrapper from "./components/AuthWrapper.jsx";
import PublicRoutes from "./components/PublicRoutes";
import ProtectedRoutes from "./components/ProtectedRoutes.jsx";
import RoleRoutes from "./components/RoleRoutes.jsx";

import Login from "./pages/public/Login/Index.jsx";

import Layout from "./layout/Index.jsx";
import PageTitleProvider from "./components/common/PageTitleProvider";

import Dashboard from "./pages/private/Dashboard/Index";
import Products from "./pages/private/Products/Index";

import ViewProductItem from "./pages/private/Products/components/ViewProductItem/Index.jsx";
import PurchaseOrders from "./pages/private/PurchaseOrders/Index.jsx";
import CreatePurchaseOrder from "./pages/private/PurchaseOrders/CreatePurchaseOrder/Index.jsx";
import ViewPurchaseOrder from "./pages/private/PurchaseOrders/ViewPurchaseOrder.jsx";
import Orders from "./pages/private/Orders/Index.jsx";

import ProductGroups from "./pages/private/ProductGroups/Index.jsx";
import Suppliers from "./pages/private/Suppliers/Index.jsx";
import Companies from "./pages/private/Companies/Index.jsx";
import Locations from "./pages/private/Locations/Index.jsx";
import Warehouses from "./pages/private/Warehouses/Index.jsx";

import Reports from "./pages/private/Reports/Index.jsx";
import Users from "./pages/private/Users/Index.jsx";
import Inventory from "./pages/private/Inventory/Index.jsx";

import Ecommerce from "./pages/private/Ecommerce/Index";

import ViewOrder from "./pages/private/Orders/ViewOrder/Index.jsx";
import CustomerOrder from "./pages/private/CustomerOrder/Index.jsx";
import ViewCustomerOrder from "./pages/private/CustomerOrder/ViewOrder/Index.jsx";

function App() {
  const routes = [
    {
      title: "Products",
      subTitle: "product management",
      path: "/products",
      element: <Products />,
    },
    // {
    //   title: "My Orders",
    //   subTitle: "view your orders here",
    //   path: "/customerOrders",
    //   element: <CustomerOrder />,
    // },
    // {
    //   title: "Purchase Orders",
    //   subTitle: "purchase order management",
    //   path: "/purchaseOrders",
    //   element: <PurchaseOrders />,
    // },
    // {
    //   title: "View Order",
    //   subTitle: "view order full details",
    //   path: "/orders/:orderId",
    //   element: <ViewOrder />,
    //   isWithBackButton: true,
    // },
    // {
    //   title: "View Order",
    //   subTitle: "view order full details",
    //   path: "/customerOrders/:orderId",
    //   element: <ViewCustomerOrder />,
    //   isWithBackButton: true,
    // },
    // {
    //   title: "View Product Item",
    //   subTitle: "view product item full details",
    //   path: "/productItems/:productCategoryId/:productItemId",
    //   element: <ViewProductItem />,
    //   isWithBackButton: true,
    // },
    // {
    //   title: "Create Purchase Order",
    //   subTitle: "create purchase order here",
    //   path: "/purchaseOrders/create",
    //   element: <CreatePurchaseOrder />,
    // },
    // {
    //   title: "Orders",
    //   subTitle: "create purchamanage order here",
    //   path: "/orders",
    //   element: <Orders />,
    // },
    // {
    //   title: "View Purchase Order",
    //   subTitle: "view your purchase order here",
    //   path: "/purchaseOrders/:purchaseOrderId",
    //   element: <ViewPurchaseOrder />,
    //   isWithBackButton: true,
    // },
    // {
    //   title: "Products",
    //   subTitle: "product management",
    //   path: "/products",
    //   element: <Products />,
    // },
    // {
    //   title: "Inventory",
    //   subTitle: "inventory management",
    //   path: "/inventory",
    //   element: <Inventory />,
    // },
    // {
    //   title: "Users",
    //   subTitle: "user management",
    //   path: "/users",
    //   element: <Users />,
    // },
    // {
    //   title: "Reports",
    //   subTitle: "view reports here",
    //   path: "/reports",
    //   element: <Reports />,
    // },
    // {
    //   title: "Product Groups",
    //   subTitle: "use to categorize products",
    //   path: "/productGroups",
    //   element: <ProductGroups />,
    // },
    // {
    //   title: "Suppliers",
    //   subTitle: "supplier management",
    //   path: "/suppliers",
    //   element: <Suppliers />,
    // },
    // {
    //   title: "Companies",
    //   subTitle: "customer/company management",
    //   path: "/companies",
    //   element: <Companies />,
    // },
    // {
    //   title: "Locations",
    //   subTitle: "product/item location management",
    //   path: "/locations",
    //   element: <Locations />,
    // },
    // {
    //   title: "Warehouses",
    //   subTitle: "warehouse management",
    //   path: "/warehouses",
    //   element: <Warehouses />,
    // },
    // {
    //   title: "Ecommerce",
    //   subTitle: "place your order here",
    //   path: "/ecommerce",
    //   element: <Ecommerce />,
    // },
    // {
    //   title: "Ecommerce",
    //   subTitle: "place your order here",
    //   path: "/ecommerce",
    //   element: <Ecommerce />,
    // },
    // {
    //   title: "My Orders",
    //   subTitle: "view your orders here",
    //   path: "/customerOrders",
    //   element: <CustomerOrder />,
    // },
  ];

  const customerRoutes = [];

  return (
    <Routes>
      <Route path="/" element={<AuthWrapper />}>
        <Route path="/" element={<PublicRoutes />}>
          <Route path="/login" element={<Login />} />
        </Route>
        <Route path="/" element={<ProtectedRoutes />}>
          <Route path="/" element={<Layout />}>
            <Route
              path="/dashboard"
              element={
                <PageTitleProvider
                  route={{
                    title: "Dashboard",
                    subTitle: "track, analyze, and optimize your operations",
                  }}
                >
                  <Dashboard />
                </PageTitleProvider>
              }
            />
            <Route path="/" element={<RoleRoutes userType="All" />}>
              {routes.map((route) => (
                <Route
                  key={route.path}
                  path={route.path}
                  element={
                    <PageTitleProvider route={route}>
                      {route.element}
                    </PageTitleProvider>
                  }
                />
              ))}
            </Route>
            {/* <Route path="/" element={<RoleRoutes userType="Customer" />}>
              {customerRoutes.map((route) => (
                <Route
                  key={route.path}
                  path={route.path}
                  element={
                    <PageTitleProvider route={route}>
                      {route.element}
                    </PageTitleProvider>
                  }
                />
              ))}
            </Route> */}
          </Route>
        </Route>
      </Route>
      <Route path="/404" element={<div>Page Not Found</div>} />
      <Route path="*" element={<Navigate to="/404" />} />
    </Routes>
  );
}

export default App;
