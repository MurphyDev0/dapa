// Közös Chart.js beállítások
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
};

// Napi bevételek diagram
function initDailyRevenueChart(data) {
    const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [{
                label: 'Napi bevétel',
                data: data.revenues,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Bevétel (Ft)'
                    }
                }
            }
        }
    });
}

// Termékenkénti eladások diagram
function initProductSalesChart(data) {
    const ctx = document.getElementById('productSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.products,
            datasets: [{
                label: 'Eladott mennyiség',
                data: data.quantities,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Darab'
                    }
                }
            }
        }
    });
}

// Kategóriánkénti eladások diagram
function initCategorySalesChart(data) {
    const ctx = document.getElementById('categorySalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.categories,
            datasets: [{
                data: data.totals,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ]
            }]
        },
        options: commonOptions
    });
}

// Bevétel-költség kimutatás diagram
function initRevenueExpenseChart(data) {
    const ctx = document.getElementById('revenueExpenseChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.dates,
            datasets: [
                {
                    label: 'Bevétel',
                    data: data.revenues,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                },
                {
                    label: 'Költség',
                    data: data.expenses,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Összeg (Ft)'
                    }
                }
            }
        }
    });
}

// Fizetési módok diagram
function initPaymentMethodsChart(data) {
    const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.methods,
            datasets: [{
                data: data.amounts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)'
                ]
            }]
        },
        options: commonOptions
    });
}

// Új vs visszatérő vásárlók diagram
function initCustomerTypeChart(data) {
    const ctx = document.getElementById('customerTypeChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Új vásárlók', 'Visszatérő vásárlók'],
            datasets: [{
                data: [data.new, data.returning],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(54, 162, 235, 0.5)'
                ]
            }]
        },
        options: commonOptions
    });
}

// Vásárlói demográfia diagram
function initCustomerDemographicsChart(data) {
    const ctx = document.getElementById('customerDemographicsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.ageGroups,
            datasets: [
                {
                    label: 'Férfi',
                    data: data.male,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                },
                {
                    label: 'Nő',
                    data: data.female,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)'
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Vásárlók száma'
                    }
                }
            }
        }
    });
}

// Kampány hatékonyság diagram
function initCampaignEffectivenessChart(data) {
    const ctx = document.getElementById('campaignEffectivenessChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.campaigns,
            datasets: [
                {
                    label: 'Konverziós ráta (%)',
                    data: data.conversionRates,
                    yAxisID: 'y',
                    backgroundColor: 'rgba(75, 192, 192, 0.5)'
                },
                {
                    label: 'ROI (%)',
                    data: data.roi,
                    yAxisID: 'y1',
                    backgroundColor: 'rgba(255, 206, 86, 0.5)'
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Konverziós ráta (%)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'ROI (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Email marketing statisztikák diagram
function initEmailMarketingChart(data) {
    const ctx = document.getElementById('emailMarketingChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [
                {
                    label: 'Megnyitási arány (%)',
                    data: data.openRates,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Kattintási arány (%)',
                    data: data.clickRates,
                    borderColor: 'rgb(255, 206, 86)',
                    tension: 0.1
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Arány (%)'
                    }
                }
            }
        }
    });
}

// Látogatottsági statisztikák diagram
function initVisitorStatsChart(data) {
    const ctx = document.getElementById('visitorStatsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [
                {
                    label: 'Látogatások',
                    data: data.pageviews,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Egyedi látogatók',
                    data: data.uniqueVisitors,
                    borderColor: 'rgb(255, 206, 86)',
                    tension: 0.1
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Látogatók száma'
                    }
                }
            }
        }
    });
}

// Bounce rate elemzés diagram
function initBounceRateChart(data) {
    const ctx = document.getElementById('bounceRateChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [{
                label: 'Bounce rate (%)',
                data: data.bounceRates,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Bounce rate (%)'
                    }
                }
            }
        }
    });
}

// Régiónkénti eladások diagram
function initRegionalSalesChart(data) {
    const ctx = document.getElementById('regionalSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.regions,
            datasets: [{
                label: 'Eladások',
                data: data.sales,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Eladások (Ft)'
                    }
                }
            }
        }
    });
}

// Nemzetközi eladások diagram
function initInternationalSalesChart(data) {
    const ctx = document.getElementById('internationalSalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.countries,
            datasets: [{
                data: data.sales,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ]
            }]
        },
        options: commonOptions
    });
}

// Vásárlói elégedettség diagram
function initCustomerSatisfactionChart(data) {
    const ctx = document.getElementById('customerSatisfactionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['1', '2', '3', '4', '5'],
            datasets: [{
                label: 'Értékelések száma',
                data: data.ratings,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Értékelések száma'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Értékelés'
                    }
                }
            }
        }
    });
}

// Visszaküldések elemzése diagram
function initReturnsAnalysisChart(data) {
    const ctx = document.getElementById('returnsAnalysisChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.reasons,
            datasets: [
                {
                    label: 'Visszaküldések száma',
                    data: data.counts,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Visszaküldések száma'
                    }
                }
            }
        }
    });
}

// Diagramok inicializálása az oldal betöltésekor
document.addEventListener('DOMContentLoaded', function() {
    // Az adatokat AJAX kéréssel töltjük be
    fetch('get_report_data.php?start_date=' + document.querySelector('[name="start_date"]').value + 
          '&end_date=' + document.querySelector('[name="end_date"]').value)
        .then(response => response.json())
        .then(data => {
            initDailyRevenueChart(data.salesData);
            initProductSalesChart(data.productSales);
            initCategorySalesChart(data.categorySales);
            initRevenueExpenseChart(data.financialData);
            initPaymentMethodsChart(data.paymentMethods);
            initCustomerTypeChart(data.customerTypes);
            initCustomerDemographicsChart(data.demographics);
            initCampaignEffectivenessChart(data.campaigns);
            initEmailMarketingChart(data.emailStats);
            initVisitorStatsChart(data.visitorStats);
            initBounceRateChart(data.bounceRates);
            initRegionalSalesChart(data.regionalSales);
            initInternationalSalesChart(data.internationalSales);
            initCustomerSatisfactionChart(data.satisfaction);
            initReturnsAnalysisChart(data.returns);
        })
        .catch(error => console.error('Hiba történt az adatok betöltése közben:', error));
}); 