import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-vaccum-cleanner',
  templateUrl: './vaccum-cleanner.component.html',
  styleUrls: ['./vaccum-cleanner.component.css'],
  providers: [DatePipe]
})
export class VaccumCleannerComponent implements OnInit {

  results: any = [];
  equipments;
  products;
  labours;
  selected=[];
  from_date='';
  to_date='';
  usages;
  constructor(private service: DataAccessService,private datePipe: DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getProducts();
    this.getLabours();
    this.getEquipments();
    this.getUsages();
  }
  getUsages(){
    this.service.get('store/vaccum.php?type=getUsages&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.usages=response;
    });
  }
  getEquipments(){
    this.service.get('store/equipment.php?type=getVaccum').subscribe(response=>{
      this.equipments=response;
    });
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response=>{
      this.labours=response;
    });
  }
  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }

 

  save(data){
    if(!data.valid){
      alertify.error("all fields are required!");
      return;
    }
    this.service.post('store/vaccum.php?type=saveUsage',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']='success'){
        this.getUsages();
        alertify.success("Save successfully!")
      }else{
        alertify.success("Failed:an error occured!")
      }
    });
  }
  downloadLog(){
    this.service.open('store/vaccum.php?type=downloadUsages&from_date='+this.from_date+'&to_date='+this.to_date);
  }

}
