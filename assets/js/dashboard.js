/**
 * SkillSwap Campus - Admin & Student Dashboard Chart.js Integration
 */

document.addEventListener('DOMContentLoaded', function () {
  // Chart 1: Skill Demand vs Supply (Admin Analytics Page)
  const chartDemandSupplyEl = document.getElementById('chartSkillDemandSupply');
  if (chartDemandSupplyEl && typeof Chart !== 'undefined') {
    const ctx = chartDemandSupplyEl.getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Python', 'UI/UX Design', 'C++', 'Arduino', 'Web Dev', 'Figma', 'Java', 'Robotics'],
        datasets: [
          {
            label: 'Students Teaching',
            data: [12, 9, 14, 8, 15, 7, 10, 6],
            backgroundColor: 'rgba(79, 70, 229, 0.85)',
            borderRadius: 6
          },
          {
            label: 'Students Wanting to Learn',
            data: [18, 15, 9, 11, 12, 16, 8, 9],
            backgroundColor: 'rgba(14, 165, 233, 0.85)',
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Skill Supply vs Demand Breakdown' }
        },
        scales: {
          y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // Chart 2: Department Participation (Doughnut Chart)
  const chartDepartmentEl = document.getElementById('chartDepartmentParticipation');
  if (chartDepartmentEl && typeof Chart !== 'undefined') {
    const ctx = chartDepartmentEl.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Computer Engg', 'Electronics', 'Information Tech', 'Mechanical Engg', 'Civil & AI'],
        datasets: [{
          data: [35, 25, 20, 12, 8],
          backgroundColor: ['#4f46e5', '#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }

  // Chart 3: Monthly Skill Exchange Growth (Line Chart)
  const chartMonthlyEl = document.getElementById('chartMonthlyExchanges');
  if (chartMonthlyEl && typeof Chart !== 'undefined') {
    const ctx = chartMonthlyEl.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
        datasets: [{
          label: 'Completed Exchanges',
          data: [12, 19, 27, 34, 45, 52, 68, 85],
          fill: true,
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: '#4f46e5'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
          x: { grid: { display: false } }
        }
      }
    });
  }
});
