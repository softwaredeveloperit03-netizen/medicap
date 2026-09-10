import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subscription } from 'rxjs';
import { interval } from 'rxjs';
declare let alertify;
 
 
@Component({
  selector: 'app-demodash',
  templateUrl: './demodash.component.html',
  styleUrls: ['./demodash.component.css']
})
export class DemodashComponent implements OnInit {

  selectedDept: string = 'administration';
  software_type:any;
  department = '';
  type = '';
  plant_type:any;
  plant_id:any;
  emp_id: string;
  // isLogin: boolean = false;
  ComponentName: string;
  plant_name: string;
  Ho=true;
  myDate:Date;
  activeTab: any;
  persons: any[]= [];
  employees: any;

  constructor(private service: DataAccessService, private router: Router) {
    setInterval(() => {
      this.myDate = new Date();
    }, 1);
    console.log(this.software_type + " " + this.type)
    console.log('ji');
    console.log(this.department);
  }
  isjadugar = false;

  ngOnInit() {
     this.ComponentName = 'Dashboard';
    this.get_rights();
    this.getEmployees();
    // this.service.checkUserAccess(this.ComponentName);

    this.department = localStorage.getItem('department');
    this.type = localStorage.getItem('type');
    console.log(this.type);
    this.software_type = this.service.getPlantConfigFields('software_type');
    this.plant_name = this.service.getPlantConfigFields('plant_name');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = localStorage.getItem('plant_id') ;
    let tempClientData = JSON.parse(localStorage.getItem('client_info'));
    // if (this.software_type == null) {
    //   this.service.getData('https://gmpsoftwareindia.com/admin/api/clients/client_data_without_token.php?type=get_client_data_by_id&id=' + localStorage.getItem("plant_id")).subscribe(response => {
    //     localStorage.setItem('client_info', JSON.stringify(response));
    //     console.log('client_info',response);
    //     this.software_type = this.service.getPlantConfigFields('software_type');
    //   });
    // }
  }


  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  //*ngIf="plant_head == 'Yes'"

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
      this.plant_head=this.rights[0].plant_head
      this.shift_allocator=this.rights[0].shift_allocator
    });
  }

  selectDept(dept: string): void {
    this.selectedDept = dept;
  }

  



  isPlant_head = false;
  ismaster = false;
  isadmin = false;
  isinventory = false;
  isopertaion = false;
  isquality = false;
  isengg = false;
  isreserch = false;
  isvendor = false;
  isaccount = false;
  ispurchase = false;
  iswelcome = true;
  isFinance = false;
  isPlanning = false;
  isIT = false;
  isExternalPanel = false;
  isRegulatory = false;
  isManagement = false;
  isQa = false;
  isSecurity = false;


  handleClick(value){
    console.log('value',value)
  this.activeTab = value;
    if(value == 'Masters'){
      this.ismaster = true;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
      
    }
    else if(value == 'plant_head'){
      this.ismaster = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = true;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      

    }
    else if(value == 'Administration'){
      this.ismaster = false;
      this.isadmin = true;
      this.isaccount = false;
      this.ispurchase = false;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }
    else if(value == 'Accounts'){
      this.ismaster = false;
      this.isadmin = false;
      this.isaccount = true;
      this.ispurchase = false;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }
    else if(value == 'Purchase'){
      this.ismaster = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = true;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'Planning'){
      this.ismaster = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isinventory = false;
       this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = true;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }
    else if(value == 'Inventory'){
      this.ismaster = false;
       this.isinventory = true;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
      
     }else if(value == 'Operations'){

      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = true;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      

    }else if(value == 'Quality'){

      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = true;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'Engginering'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = true;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'IT'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = true;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'Reserch'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = true;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'Vendors'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = true;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'external_panel'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = true;
      this.isRegulatory = false;
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
      
    } else if(value == 'Regulatory'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = true;      
      this.isManagement = false
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'management'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;      
      this.isManagement = true;
      this.isQa = false;
      this.isSecurity = false;
      
    }else if(value == 'Security'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;      
      this.isManagement = false;
      this.isQa = false;
      this.isSecurity = true;
      
    }else if(value == 'QA'){
      this.ismaster = false;
      this.isinventory = false;
      this.isadmin = false;
      this.isaccount = false;
      this.ispurchase = false;
      this.isopertaion = false;
      this.isquality = false;
      this.isengg = false;
      this.isreserch = false;
      this.isvendor = false;
      this.iswelcome = false;
      this.isPlant_head = false;
      this.isFinance = false;
      this.isPlanning = false;
      this.isIT = false;
      this.isExternalPanel = false;
      this.isRegulatory = false;      
      this.isManagement = false;
      this.isQa = true;
      this.isSecurity = false;
    }
  }
  getEmployees() {
    // this.service.get('hr/emp.php?type=getemp').subscribe(response => {
    this.service
      .get('hr/employee.php?type=getEmployeesList&department_name=')
      .subscribe((response) => {
        this.employees = response;
        console.log('this.employees',this.employees);
        this.employees.forEach((item: any, index: any) => {
            this.persons.push(item);
        });
        this.persons.splice(0,5);
        this.persons = this.persons.map(person => ({
          ...person,
          fullImgSrc: this.service.url + '../../upload/employee/' + person.photo
        }));
      });
  }
  getFullImageUrl(imgSrc: string): string {
    return this.service.url + '../../upload/employee/' + imgSrc;
  }


  loggedin(value,softwareType:string){
    console.log('value',value);


    if (value == 'master') {
      if (
        localStorage.getItem('department') == 'Quality Control' ||
        localStorage.getItem('department') == 'Quality Assurance' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/master']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'packing') {
      this.router.navigate(['/packing']);
    } else if (value == 'production') {
      if (
        localStorage.getItem('department') == 'Production' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (
          this.plant_type != 'API/ Excipients' &&
          this.software_type == 'Pharma ERP'
        ) {
          this.router.navigate(['/fproduction']);
        } else if (
          this.plant_type == 'API/ Excipients' &&
          this.software_type == 'Pharma ERP'
        ) {
          this.router.navigate(['/production']);
        } else if (
          this.plant_type != 'API/ Excipients' &&
          this.software_type == 'PaperLess GMP Platinum (Regulated)' &&
          this.plant_id != '86'
        ) {
          this.router.navigate(['/fproduction']);
        } else if (
          this.plant_type == 'API/ Excipients' &&
          this.software_type == 'PaperLess GMP Platinum (Regulated)'
        ) {
          this.router.navigate(['/prod-f-ebmr']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'exportSales') {
      if (
        localStorage.getItem('department') == 'Quality Assurance' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/export']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'admin') {
      if (
        localStorage.getItem('department') == 'Human Resource' ||
        localStorage.getItem('department') == 'Admin' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-admin']);
        } else {
          this.router.navigate(['/admin']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'security') {
      if (
        localStorage.getItem('department') == 'Security' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/security']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'qa') {
      if (
        localStorage.getItem('department') == 'Quality Assurance' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/qa']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'qc') {
      if (
        localStorage.getItem('department') == 'Quality Control' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/qc']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'it') {
      if (
        localStorage.getItem('department') == 'Human Resource' ||
        localStorage.getItem('department') == 'IT' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/it']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'engineering') {
      if (
        localStorage.getItem('department') == 'Engineering' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/engineering']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'rnd') {
      if (
        localStorage.getItem('department') == 'Rnd' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/rnd']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'vp') {
      this.router.navigate(['/vendor-dashboard/vendors']);
    } else if (value == 'cp') {
      this.router.navigate(['/']);
    } else if (value == 'lp') {
      this.router.navigate(['/']);
    } else if (value == 'mp') {
      this.router.navigate(['/']);
    } else if (value == 'account') {
      if (
        localStorage.getItem('department') == 'Account' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-account']);
        } else {
          this.router.navigate(['/account']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'planning') {
      if (
        localStorage.getItem('department') == 'Planning' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-planning']);
        } else {
          this.router.navigate(['/planning']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'purchase') {
      if (
        localStorage.getItem('department') == 'Purchase' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-purchase']);
        } else {
          this.router.navigate(['/purchase']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'finish') {
      if (
        localStorage.getItem('department') == 'Dispatch' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/dispatch']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'generalStore') {
      if (
        localStorage.getItem('department') == 'Store' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/engi-store']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'store') {
      if (
        localStorage.getItem('department') == 'Store' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/store']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'marketing') {
      if (
        localStorage.getItem('department') == 'Marketing' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          // this.router.navigate(['/ho-marketing']);
          this.router.navigate(['/marketing']);
        } else {
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'hr') {
      if (
        localStorage.getItem('department') == 'Human Resource' ||
        localStorage.getItem('department') == 'master'
      ) {
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-hr']);
        } else {
          this.router.navigate(['/hr']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'management') {
      if (
        localStorage.getItem('department') == 'Management' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/marketing']);
        if (this.plant_id == '137') {
          this.router.navigate(['/ho-management']);
        } else {
          this.router.navigate(['/management']);
        }
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'calibration') {
      this.router.navigate(['/calibration']);
    } else if (value == 'ehs') {
      if (
        localStorage.getItem('department') == 'EHS' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/ehs']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'ipqc') {
      if (
        localStorage.getItem('department') == 'IPQC ' ||
        localStorage.getItem('department') == 'master'
      ) {
        this.router.navigate(['/ipqc']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'plant_head') {
      if (
        localStorage.getItem('department') == '' ||
        localStorage.getItem('department') == 'master' ||
        this.plant_head == 'Yes'
      ) {
        this.router.navigate(['/plant_head']);
        console.log('planthead=' + this.plant_head);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'autoPurchase') {
      console.log('value', value);
      if (localStorage.getItem('department') == 'master') {
        this.router.navigate(['/autopurchase']);
      } else {
        alertify.error('Access Denied');
      }
    } else if (value == 'regulatory') {
      if (localStorage.getItem('department') == 'master' || localStorage.getItem('department') == 'Regulatory') {
        if (this.plant_id == '137') {
          this.router.navigate(['/regulatory']);
        } else {
          this.router.navigate(['/regulatory-new']);
        }
      } else {
        alertify.error('Access Denied');
      }
    }
  }

  toggleSidebar() {
    this.service.isSidebarOpen = !this.service.isSidebarOpen;
  }
}


