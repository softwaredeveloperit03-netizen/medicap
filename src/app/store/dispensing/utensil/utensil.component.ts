import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-utensil',
  templateUrl: './utensil.component.html',
  styleUrls: ['./utensil.component.css'],
  providers:[DatePipe]
})
export class UtensilComponent implements OnInit {
  operators;
  from_date='';
  to_date='';
  today='';
  employess;
  results: any = [];
  constructor(private service :DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getOperator();
    this.getUtensilsRecords();
    this.getEmp();
  }
  getUtensilsRecords(){
    this.service.get('store/utensil.php?type=getUtensil&from_date='+this.from_date+"&to_date="+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  getEmp(){
    this.service.get('hr/employee.php?type=getEmployess').subscribe(response =>{
      this.employess = response;
    });
  }
  download()
  {
    this.service.open('store/utensil.php?type=downloadUtensil&from_date='+this.from_date+"&to_date="+this.to_date)

  }
  getOperator(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.operators = response;
    });
  }
  saveRecords(data){
    if(!data.valid)
    {
      alertify.error("All Fields Are Required !!!");
      return;
    }
    this.service.post('store/utensil.php?type=saveUtensil',JSON.stringify(data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getUtensilsRecords();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  check(id){
    this.service.get('store/utensil.php?type=checkUtensil&id='+id).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getUtensilsRecords();
        alertify.success('Record Check successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
}
