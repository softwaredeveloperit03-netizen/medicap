import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isMobile = false;
  labours;
  item = [];
  labour_name='';
  category='';
  results;
  todaysLabours;
  isNew= false;
  isPresent =false;
  departments;
  selectAtt =[];
  isAbsent=false;
  constructor(private service: DataAccessService) {
    this.isMobile = this.service.isMobile;
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getActiveLabours();
    this.getTodaysLabors();
    this.get_rights();
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
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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


  getTodaysLabors() {
    this.todaysLabours=[];
    this.service.get('security/labour.php?type=getTodaysLabors').subscribe((response: any) => {
      this.todaysLabours = response;
      this.filterItem()
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getActiveLabours() {
    this.service.get('security/labour.php?type=getActiveLabours').subscribe(response => {
      this.labours = response;
    });
  }

  labourEntry(labour_id) {
    this.service.get('security/labour.php?type=labourEntry&labour_id='+labour_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getTodaysLabors();
        this.isNew = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

  exitLabour(id) {
    this.service.get('security/labour.php?type=exitLabour&id='+id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getTodaysLabors();
        this.isNew = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

  present(index,value){
    this.selectAtt = this.todaysLabours[index];
    console.log('button', this.selectAtt);
    this.isPresent =true;
  }

  absent(id,value){
    this.isAbsent =true;
  }

  presentatt(labour_id ,status) {
    this.service.get('security/labour.php?type=labourEntry&status=' + status +'labour_id ='+labour_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getTodaysLabors();
        this.isNew = false;
      } else {
        alertify.error('An error occured!');
      }
    });
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.todaysLabours.length; i++) {
      let material = this.todaysLabours[i];
      if (material['labour_name'].toUpperCase().includes(this.labour_name.toUpperCase())&&material['category'].toUpperCase().includes(this.category.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }

  AllRecord(){
    this.item =this.todaysLabours;
    this.labour_name='';
    this.category='';
    
}

  download(){
    this.service.open('security/labour.php?type=downloadTodaysLaborsLog');
  }

}
