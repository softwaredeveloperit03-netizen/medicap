import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  results;
  lists;
  employees;

  selectedResult = [];
  constructor(private service:DataAccessService) { 
  }

  ngOnInit() {
    this.getList();
    this.getEmployee();
  }
  
  getEmployee(){
    this.service.get('employee.php?type=getEngineeringPersons').subscribe(response=>{
      this.employees=response;
    });
  }

  getList(){
    this.service.get('engineering/earthing.php?type=getEarthingPoints').subscribe(response=>{
      this.lists=response;
    });
  }

  download(){
    this.service.open('engineering/earthing.php?type=downloadEarthingPoints')
  }
  save(data){ 
    if (!data.valid) {
      alertify.error('all fields are required');
      return;
    }
    // this.lists=data.value;
    this.service.post('engineering/earthing.php?type=saveEarthingTest',JSON.stringify(this.lists)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success("Saved successfully!")
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }

}
