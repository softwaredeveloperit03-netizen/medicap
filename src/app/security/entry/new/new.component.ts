import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {
  visitorName='';
  isMobile = false;

  isNew = false;
  isPhoto=false;

 
  

 
  results;
  category;
  departments;
  employees;
  from_date = '';
  to_date = '';
  results1=[];
  department_name = '';
  constructor(private service: DataAccessService,private datepipe:DatePipe, private router: Router) {
  
  }

  ngOnInit() {
    this.getVechileDetails();
    // this.getDepartments();
  }


  getVechileDetails() {
    this.service.get('security/entry.php?type=getVechileDetails&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name).subscribe(response => {
      this.results = response;
    });
  }


  // getDepartments() {
  //   this.service.get('common.php?type=getDepartments').subscribe(response => {
  //     this.departments = response;
  //   });
  // }

  
  saveVechile(data) {
    if(!data.valid){
      alertify.error("all field are required");
      return;
    }
    let temp = data.value;
    this.service.post('security/entry.php?type=saveVechileEntry', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Gate Pass Record Added');
        data.resetForm();
        this. getVechileDetails();
        this.isNew = false;
        this.router.navigate(['/security/entry']);
      } else {
       alertify.error('An error occured');
      }
    });
  }

  exitvisitor(id) {
    this.service.get('security/entry.php?type=exitVechile&id=' + id).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Visitor Exited Successfully');
        this.getVechileDetails();
      } else {
       alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  download() {
    this.service.open('security/entry.php?type=downloadLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name);
  }
}
