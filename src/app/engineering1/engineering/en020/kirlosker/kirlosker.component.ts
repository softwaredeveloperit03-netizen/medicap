import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-kirlosker',
  templateUrl: './kirlosker.component.html',
  styleUrls: ['./kirlosker.component.css'],
  providers:[DatePipe]
})
export class KirloskerComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  plant_names;
  isNew=false;

  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

    }

  ngOnInit(): void {
    this.getPlant();
     this.get_rights();

  }

  getPlant(){
    this.service.get('engineering/chilling.php?type=getKirloskerOperations&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('engineering/chilling.php?type=downloadKirloskerOperations&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    this.service.post('engineering/chilling.php?type=saveKirloskerOperation',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.isNew=false;
        this.getPlant();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
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


}
