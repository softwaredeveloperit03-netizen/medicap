
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  visitorName='';
  isMobile = false;
  results;
  isView=false;
  selectedResult=[];
  isNew = false;
  isPhoto=false;
  item_name;
  bal_qty;
  available_qty;
  consum_qty;
  filterargs;
  employeeList: Array<string>;

  meeting: string;
  employeeMasterList;
  newEmployee: Array<any>;
  item = [];
  category;
  departments;
  employees;
  from_date = '';
  to_date = '';
  results1=[];
  department_name = '';
  designations;
  department='';
  constructor(private service: DataAccessService,private datepipe:DatePipe,private router: Router) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getAidDetails();
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  getAidDetails() {
    this.service.get('security/firstaid.php?type=getFisrtAidDetails&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name).subscribe(response => {
      this.results = response;
      this.filterItem();
    });
  }

  download() {
    this.service.open('security/firstaid.php?type=downloadFirstAidLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name);
  }

  updateFirstAid(data){
    if(!data.valid){
      alertify.error("all field are required");
      return;
    }

    let temp = data.value;
    this.service.post('security/firstaid.php?type=updateFirstAid&id=' + this.selectedResult['id'] , JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Data Added Successfully');
        data.resetForm();
        this.router.navigate(['/security/aid/consumption']);
      } else {
       alertify.error('An error occured');
      }
    });
  }


  
filterItem() {
  this.item = [];
  for (let i = 0; i < this.results.length; i++) {
    let material = this.results[i];
    if (material['department'].toUpperCase().includes(this.department.toUpperCase())) {
      this.item[this.item.length] = material;
    }
  }
}

AllRecord(){
  this.item=this.results;
  this.department='';
}

}
