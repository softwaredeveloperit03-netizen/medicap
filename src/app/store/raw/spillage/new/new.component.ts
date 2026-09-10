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
  material;
  materials;
  material_code='';
  selectedData=[];
  selectedDate=[];
  material_type='';
  qty=0;
  batch_no='';
  material_subtype='';
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getMaterialType();
  }

  getName(value){
    this.service.get('store/spillage.php?type=getMaterials&material_subtype='+ value).subscribe(response=>{
      this.materials=response;
    });
  }

  types;
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types= response;
    });
  }



 
 
  


  checkQty(){
     if (+this.selectedDate['qty'] < this.qty) {
      alertify.error('Spillage Qty is not greater than Available Qty. Available Qty:' + this.selectedDate['qty']);
      this.qty = 0;
      return;
    }
  }

  getBatches(index){
    index=index-1;
    this.selectedData=this.materials[index];
  }

  getDate(index){
    index=index-1;
    let AllData=this.selectedData['batches'];
    this.selectedDate=AllData[index]
  }
 

  saveSpillage(data){
    if(!data.valid){
      alertify.error('All feilds are required');
    }
    this.service.post('store/spillage.php?type=saveSpillage',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data Added Successfuly');
        this.router.navigate(['/raw/spillage']);
        data.resetForm();
      }else{
        alertify.error('some error occured!Try again');
      }
    });
  }

}



 