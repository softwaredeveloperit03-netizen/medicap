import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }


  u_type = "User";


  departments;
  employees;
  sections;

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  equipments;
 
  getequipment(value) {
    this.service.get('common.php?type=getbal_idForCOnsu&department1='+value).subscribe(response => {
      this.equipments = response;
    });
  }
 
  getEmployees(value,index){
    this.service.get('hr/shift.php?type=getEmployees&department1='+value).subscribe(response=>{
      this.employees=response;
    });

    this.sections=[];
    this.sections = this.departments[index-1]['sections'];

    this.getequipment(value);
  }

  selectedEqp =[];

  selectedEquipment(index){
    this.selectedEqp = this.equipments[index-1];
  }
  selectedEmp =[];

  selectedEmployees(index){
    this.selectedEmp = this.employees[index-1];
  }






  save(data){
    if(!data.valid){
      alertify.error('all field are required');
      return;
    }
    this.service.post('common.php?type=saveItConsumableLog',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
         alertify.success("save successfully");
         data.reset();
      }else{
        alertify.error("Failed:an Error occures");
      }
    })
  }

}
