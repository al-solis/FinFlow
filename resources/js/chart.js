import Chart from "chart.js/auto";

document.addEventListener("DOMContentLoaded", function () {
    // Income Statement Chart
    const incomeChartEl = document.getElementById("incomeStatementChart");
    if (incomeChartEl) {
        const revenue = JSON.parse(incomeChartEl.dataset.revenue || "[]");
        const expenses = JSON.parse(incomeChartEl.dataset.expenses || "[]");

        new Chart(incomeChartEl, {
            type: "bar",
            data: {
                labels: revenue.map((r) => r.account_code),
                datasets: [
                    {
                        label: "Revenue",
                        data: revenue.map((r) => r.balance),
                        backgroundColor: "rgba(34, 197, 94, 0.6)",
                        borderColor: "rgb(34, 197, 94)",
                        borderWidth: 1,
                    },
                    {
                        label: "Expenses",
                        data: expenses.map((e) => e.balance),
                        backgroundColor: "rgba(239, 68, 68, 0.6)",
                        borderColor: "rgb(239, 68, 68)",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    title: {
                        display: true,
                        text: "Revenue vs Expenses",
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    // Balance Sheet Chart
    const balanceChartEl = document.getElementById("balanceSheetChart");
    if (balanceChartEl) {
        const assets = JSON.parse(balanceChartEl.dataset.assets || "[]");
        const liabilities = JSON.parse(
            balanceChartEl.dataset.liabilities || "[]",
        );
        const equity = JSON.parse(balanceChartEl.dataset.equity || "[]");

        new Chart(balanceChartEl, {
            type: "doughnut",
            data: {
                labels: ["Assets", "Liabilities", "Equity"],
                datasets: [
                    {
                        data: [
                            assets.reduce((sum, a) => sum + a.balance, 0),
                            liabilities.reduce((sum, l) => sum + l.balance, 0),
                            equity.reduce((sum, e) => sum + e.balance, 0),
                        ],
                        backgroundColor: [
                            "rgba(59, 130, 246, 0.7)",
                            "rgba(234, 179, 8, 0.7)",
                            "rgba(168, 85, 247, 0.7)",
                        ],
                        borderColor: [
                            "rgb(59, 130, 246)",
                            "rgb(234, 179, 8)",
                            "rgb(168, 85, 247)",
                        ],
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    title: {
                        display: true,
                        text: "Balance Sheet Composition",
                    },
                },
            },
        });
    }
});
