import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New Deviation', route: 'new', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'review', title: 'Deviation For Review', route: 'review', icon: 'fa-comment-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  public rawMaterialmonthly: any;
  public month: any = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];
  public Raw_Material_Monthly_Purchase_Data: any = [
    '420100',
    '223121',
    '311122',
    '434221',
    '553121',
    '624511',
    '662516',
    '321133',
    '262134',
    '443621',
    '203121',
    '301122',
  ];
  public Packing_Material_Monthly_Purchase_Data: any = [
    '220100',
    '423121',
    '511122',
    '134221',
    '253121',
    '524511',
    '662516',
    '421133',
    '162134',
    '343621',
    '603121',
    '501122',
  ];
  public Finished_Material_Monthly_Purchase_Data: any = [
    '320100',
    '123121',
    '211122',
    '334221',
    '453121',
    '324511',
    '562516',
    '221133',
    '362134',
    '543621',
    '103121',
    '201122',
  ];

  constructor(private service: DataAccessService) {
    Chart.register(...registerables);
      this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.MaterialMonthlypurchase();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
   this.service
     .get(
       'hr/employee.php?type=getrights&emp_id=' +
         localStorage.getItem('emp_id') +
         '&dep_name=' +
         localStorage.getItem('department')
     )
     .subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
       this.isapprover = this.rights[0].isapprover;
       this.qms_approver = this.rights[0].qms_approver;
       this.dept_head = this.rights[0].dept_head;
       this.isauditor = this.rights[0].isauditor;
       this.plant_head = this.rights[0].plant_head;
       this.shift_allocator = this.rights[0].shift_allocator;
     });
  }
  //---------------------------------------------------------------------------------//

  MaterialMonthlypurchase() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.rawMaterialmonthly = new Chart('MaterialWiseMonthlyChart', {
      type: 'bar',

      data: {
        labels: this.month,
        datasets: [
          {
            label: 'Raw',
            data: this.Raw_Material_Monthly_Purchase_Data,
            backgroundColor: ['#66994D'],
            borderColor: 'black',
            borderWidth: 2,
          },
          {
            label: 'Packing',
            data: this.Packing_Material_Monthly_Purchase_Data,
            backgroundColor: ['#FF6633'],
            borderColor: 'black',
            borderWidth: 2,
          },
          {
            label: 'Finished',
            data: this.Finished_Material_Monthly_Purchase_Data,
            backgroundColor: ['#FF3380'],
            borderColor: 'black',
            borderWidth: 2,
          },
        ],
      },
    });
  }
}
