import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  Chart,
  ChartConfiguration,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  Title,
  registerables,
} from 'chart.js';

declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  loggedInDept;
  purchaseData: any = [];  // Store the final_total data

  public colorArray: any = [
    '#FF6633',
    '#FFB399',
    '#FF33FF',
    '#FFFF99',
    '#00B3E6',
    '#E6B333',
    '#3366E6',
    '#999966',
    '#99FF99',
    '#B34D4D',
    '#80B300',
    '#809900',
    '#E6B3B3',
    '#6680B3',
    '#FF99E6',
    '#CCFF1A',
    '#FF1A66',
    '#E6331A',
    '#33FFCC',
    '#66664D',
    '#991AFF',
    '#E666FF',
    '#4DB3FF',
    '#1AB399',
    '#E666B3',
    '#33991A',
    '#CC9999',
    '#B3B31A',
    '#00E680',
    '#4D8066',
    '#809980',
    '#E6FF80',
    '#1AFF33',
    '#999933',
    '#FF3380',
    '#CCCC00',
    '#66E64D',
    '#4D80CC',
    '#9900B3',
    '#E64D66',
    '#4DB380',
    '#FF4D4D',
    '#99E6E6',
    '#6666FF',
  ];

  public yearlyPurchase: any;
  public fourthNightlyPurchase: any;
  public monthlyPurchase: any;
  public materialTypePurchase: any;
  public threeMonthlyPurchase: any;
  public vendorVicePurchase: any;
  public rawMaterialmonthly: any;
  public rawMaterialQuarterly: any;

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
  public quartly: any = ['jan-Mar', 'Apr-Jun', 'july-Sep', 'Oct-Dec'];

  public totalMonthlyPurchaseData: any = [
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
  public totalQuartlyPurchaseData: any = [
    '765654',
    '675841',
    '876543',
    '650000',
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

  public Raw_Material_Quartly_Purchase_Data: any = [
    '565654',
    '575841',
    '976543',
    '550000',
  ];
  public Packing_Material_Quartly_Purchase_Data: any = [
    '865654',
    '775841',
    '976543',
    '750000',
  ];
  public Finished_Material_Quartly_Purchase_Data: any = [
    '665654',
    '575841',
    '776543',
    '550000',
  ];

  public vendorName: any = [];
  public vendorPurchase: any = [];
  public vendorColor: any = [];
  public po: any = [];

  public materialType: any = [];
  RM: number = 0;
  PM: number = 0;
  FM: number = 0;

  public materialPurchase: any = [];

  plant_id: any;

  isLogin = false;
  constructor(private service: DataAccessService) {
    Chart.register(...registerables);

    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getPurchaseNotifications();
    this.AllRecord();
    this.validateUser();
    this.monthlypurchase1();
    this.quarterlypurchase();
    this.MaterialMonthlypurchase();
    this.MaterialQuarterlypurchase();
    this.vendorWisePurchase1();
    this.materialTypePurchase1();
    this.get_rights();
    // this.getPendingPurchase()
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  trainig_cordinator = 'No';
  rights;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
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
        this.trainig_cordinator = this.rights[0].trainig_cordinator;


        console.log(this.rights)
        console.log(this.isuser)
        console.log(this.ischecker)
        console.log(this.isapprover)
        console.log(this.qms_approver)
        console.log(this.dept_head)
        console.log(this.isauditor)
        console.log(this.plant_head)
        console.log(this.shift_allocator)
      });
  }

  goToDeptHomePage() {
    let loggedInDept = localStorage.getItem('department');
    console.log('loggedInDept', this.loggedInDept);
    this.service.navigateToSpecificDeptHome(loggedInDept);
  }

  AllRecord() {
    this.service
      .get('purchase/po/raw.php?type=getAllPOLog')
      .subscribe((data) => {
        if (data) {
          console.log(data);
          this.po = data;

          for (let i in this.po) {
            this.vendorName.push(this.po[i].vendor_name);
            this.vendorColor.push(this.colorArray[i]);
            this.vendorPurchase.push(this.po[i].net_total);
          }
          for (let i in this.po) {
            if (this.po[i].Po_Group == 'RM') {
              this.RM = this.RM + Math.trunc(this.po[i].net_total);
            } else if (this.po[i].Po_Group == 'PM') {
              this.PM = this.PM + Math.trunc(this.po[i].net_total);
            } else if (this.po[i].Po_Group == 'FM') {
              this.FM = this.FM + Math.trunc(this.po[i].net_total);
            }
          }
          this.materialPurchase.push(this.RM);
          this.materialPurchase.push(this.PM);
          this.materialPurchase.push(this.FM);
        }
      });
  }

  materialTypePurchase1() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.materialTypePurchase = new Chart('materialTypePurchaseChart', {
      type: 'pie',

      data: {
        labels: ['Raw', 'Packing', 'finish'],
        datasets: [
          {
            label: 'Material Type Purchase',
            data: this.materialPurchase,
            backgroundColor: ['#FF6633', '#FFB399', '#FF33FF'],
            borderColor: 'black',
            borderWidth: 2,
          },
        ],
      },
    });
  }

  monthlypurchase1() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.monthlyPurchase = new Chart('monthlyPurchaseChart', {
      type: 'bar',

      data: {
        labels: ['Raw Material', 'Packing Material', 'Finished Material'], // Labels for pie chart sections
        datasets: [
          {
          label: 'Raw', // Raw Material dataset
          data: this.Raw_Material_Monthly_Purchase_Data, // Data for Raw Material
          backgroundColor: '#66994D', // Bar color for Raw Material
          borderColor: 'black', // Border color
          borderWidth: 2, // Border width
        },
        {
          label: 'Packing', // Packing Material dataset
          data: this.Packing_Material_Monthly_Purchase_Data, // Data for Packing Material
          backgroundColor: '#FF6633', // Bar color for Packing Material
          borderColor: 'black', // Border color
          borderWidth: 2, // Border width
        },
        {
          label: 'Finished', // Finished Material dataset
          data: this.Finished_Material_Monthly_Purchase_Data, // Data for Finished Material
          backgroundColor: '#FF3380', // Bar color for Finished Material
          borderColor: 'black', // Border color
          borderWidth: 2, // Border width
        },
        ],
      },

    });
  }

  quarterlypurchase() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.threeMonthlyPurchase = new Chart('threeMonthlyPurchaseChart', {
      type: 'bar',

      data: {
        labels: this.quartly,
        datasets: [
          {
            label: 'Total Quarterly Purchase',
            data: this.totalQuartlyPurchaseData,
            backgroundColor: ['#FFFF99', '#00B3E6', '#E6B333', '#3366E6'],
            borderColor: 'black',
            borderWidth: 2,
          },
        ],
      },
    });
  }

  vendorWisePurchase1() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.vendorVicePurchase = new Chart('vendorVicePurchaseChart', {
      type: 'bar',

      data: {
        labels: this.vendorName,
        datasets: [
          {
            label: 'vendor Wise Purchase',
            data: this.vendorPurchase,
            backgroundColor: this.vendorColor,
            borderColor: 'black',
            borderWidth: 2,
          },
        ],
      },
    });
  }

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

  MaterialQuarterlypurchase() {
    Chart.register(
      LineController,
      LineElement,
      PointElement,
      LinearScale,
      Title
    );

    this.rawMaterialQuarterly = new Chart('MaterialWiseQuarterlyChart', {
      type: 'bar',

      data: {
        labels: this.quartly,
        datasets: [
          {
            label: 'Raw',
            data: this.Raw_Material_Quartly_Purchase_Data,
            backgroundColor: ['#E666B3'],
            borderColor: 'black',
            borderWidth: 2,
          },
          {
            label: 'Packing',
            data: this.Packing_Material_Quartly_Purchase_Data,
            backgroundColor: ['#66664D'],
            borderColor: 'black',
            borderWidth: 2,
          },
          {
            label: 'Finished',
            data: this.Finished_Material_Quartly_Purchase_Data,
            backgroundColor: ['#FF99E6'],
            borderColor: 'black',
            borderWidth: 2,
          },
        ],
      },
    });
  }

  checkLogin(formData) {
    const data = formData.value;
    this.service
      .post(
        'checkLogin.php?type=newLogin&username=' +
          data['username'] +
          '&password=' +
          data['password'],
        JSON.stringify(data)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          localStorage.setItem('token', response['token']);
          localStorage.setItem('department', response['department']);
          localStorage.setItem('designation', response['designation']);
          localStorage.setItem('user', response['user']);
          localStorage.setItem('checker', response['checker']);
          localStorage.setItem('approver', response['approver']);
          localStorage.setItem('username', response['username']);

          if (response['department'] == 'Purchase') {
            this.isLogin = true;
          }
        } else {
          this.isLogin = false;
        }
      });
  }

  validateUser() {
    if (
      localStorage.getItem('token') !== null &&
      localStorage.getItem('department') == 'Purchase'
    ) {
      this.isLogin = true;
    } else {
      this.isLogin = false;
    }
  }

  getPurchaseNotifications() {
    this.getPendingIndends();
    this.AllRecord1();
    this.getPendingQuotations();
    this.getPendingExpiry();
    this.getPendingVendor();
  }

  unreadQuotations = 0;
  unreadPo = 0;
  unreadindent = 0;
  unreadVendor = 0;

  getPendingIndends() {
    this.service
      .get('purchase/indent.php?type=getCheckedIndendsForNotification')
      .subscribe((response: any) => {
        this.unreadindent = response['Pending_indent'];
        if (this.unreadindent > 0) {
          alertify.warning(response['text']);
        }
      });
  }

  AllRecord1() {
    this.service
      .get('purchase/po/raw.php?type=getAllPendingPOForNotification')
      .subscribe((response: any) => {
        this.unreadPo = response['Pending_Po'];
        if (this.unreadPo > 0) {
          alertify.warning(response['text']);
        }
      });
  }

  // getPendingPurchase(): void {
  //   this.service.get('chart.php?type=getPendingPurchase').subscribe((data: any) => {
  //     // Process the fetched data and extract the values you need
  //     this.Raw_Material_Monthly_Purchase_Data = data.map(item => item.total_net); // Extract total_net values for Raw Material
  //     this.monthlypurchase1(); // Call the chart rendering method with the fetched data
  //   });
  // }



  getPendingQuotations() {
    this.service
      .get('purchase/quotation.php?type=getPendingQuotationsForNotification')
      .subscribe((response) => {
        this.unreadQuotations = response['Pending_quatation'];
        if (this.unreadQuotations > 0) {
          alertify.warning(response['text']);
        }
      });
  }

  getPendingVendor() {
    this.service
      .get('purchase/vendor.php?type=getPendingVendorsNotification')
      .subscribe((response) => {
        this.unreadVendor = response['Pending_Vendor'];
        if (this.unreadVendor > 0) {
          alertify.warning(response['text']);
        }
      });
  }

  unreadExpiry = 0;

  getPendingExpiry() {
    this.service
      .get('notification.php?type=ExpiryNotification')
      .subscribe((response) => {
        this.unreadExpiry = response['Pending_expiry'];
        if (this.unreadExpiry > 0) {
          alertify.warning(response['text']);
        }
      });
  }
}
