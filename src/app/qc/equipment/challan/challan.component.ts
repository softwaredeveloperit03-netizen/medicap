import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-challan',
  templateUrl: './challan.component.html',
  styleUrls: ['./challan.component.css'],
})
export class ChallanComponent implements OnInit {
  isView = false;
  isShow = false;
  results;
  vendors;
  selectedResult = [];
  vendor_no = '';
  from_date = '';
  to_date = '';
  status = 'approve';
  router: any;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getChallansLog();
    this.getVendors();
    this.getUnits();
    this.get_rights();
    this.getEquipments();
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

  goToNewComponent() {
    this.router.navigate(['/new']);
  }

  getChallansLog() {
    this.service
      .get(
        'qc/indicator.php?type=getChallansLog&vendor_no=' +
          this.vendor_no +
          '&to_date=' +
          this.to_date +
          '&from_date=' +
          this.from_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
equipment:any = [];
  unit:any = [];
  
  getEquipments() {
    this.service.get('common.php?type=getEquipments').subscribe(
      (response) => {
        console.log('Equipment response:', response);
        this.equipment = response || [];
      },
      (error) => {
        console.error('Error fetching equipment:', error);
      }
    );
  }
  
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(
      (response) => {
        console.log('Vendors response:', response);
        this.vendors = response || [];
      },
      (error) => {
        console.error('Error fetching vendors:', error);
      }
    );
  }
  
  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(
      (response) => {
        console.log('Units response:', response);
        this.unit = response || [];
      },
      (error) => {
        console.error('Error fetching units:', error);
      }
    );
  }
  toggleIsShow() {
    this.isShow = !this.isShow;
  }
  // this.isShow=true;
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadLog() {
    this.service.open('pdf1/store.php?type=challanLog');
  }
  download() {
    this.service.open(
      'qc/reagents.php?type=downloadChallanLog&vendor_no=' +
        this.vendor_no +
        '&to_date=' +
        this.to_date +
        '&from_date=' +
        this.from_date
    );
  }
}
