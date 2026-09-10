import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-washwater',
  templateUrl: './washwater.component.html',
  styleUrls: ['./washwater.component.css']
})
export class WashwaterComponent implements OnInit {
  products;
  person;
  equipments;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getProducts();
    this.getQAPerson();
    this.getequipments();
  }

  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }
  

  getQAPerson(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response=>{
      this.person=response;
    });
  }
  getequipments(){
    this.service.get('common.php?type=getEquipments').subscribe(response =>{
      this.equipments = response;
    });
  }
  save(data){
    if(!data.valid){
      alertify.error('All Feilds are Required');
      return;
    }
    this.service.post('qa/washwater.php?type=saveWash',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('withdrwa sample send succssfuly');
        this.router.navigate(['/qa/ipqa']);
      }else{
        alertify.error('some error Occured');
      }
    });
  }

}
