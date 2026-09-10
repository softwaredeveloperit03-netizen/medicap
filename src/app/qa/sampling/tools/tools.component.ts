import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let  alertify;
@Component({
  selector: 'app-tools',
  templateUrl: './tools.component.html',
  styleUrls: ['./tools.component.css'],
  providers:[DatePipe]
})
export class ToolsComponent implements OnInit {

  lists;
  employees;
  products;
  labours;
  from_date;
  to_date;
  isNew = false;
  selectedResult = [];
  id;
  constructor(private service: DataAccessService,private datePipe: DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getList();
    this.getProducts();
    this.getOperators();
  }
  getOperators(){
    this.service.get('employee.php?type=getQAWorkers').subscribe(response=>{
      this.labours=response;
    });  
  }
  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });  
  }
  getList(){
    this.service.get('qa/sampling.php?type=getUsageLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.lists=response;
    });
  }
  edit(index){
    this.selectedResult = this.lists[index];
    this.isNew = true;
  }

  download(){
    this.service.open('qa/sampling.php?type=downloadUsageLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){ 
    if (!data.valid) {
      alertify.error('all fields are required');
      return;
    }
    let temp=data.value;
    temp['product_code'] = temp['product_name'].product_code;
    temp['cleaned_by'] = temp['labour_name'].firstname;
    this.service.post('qa/sampling.php?type=saveUsagesEntry',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        data.reset();
        this.getList();
        alertify.success("Saved successfully!")
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }

  update(data){
    if (!data.valid) {
      alertify.error('all fields are required');
      return;
    }
    let temp=data.value; 
    temp['id'] = this.selectedResult['id'];
    this.service.post('qa/sampling.php?type=updateUsagesEntry',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        data.reset();
        this.getList();
        this.isNew = false;
        alertify.success("Saved successfully!")
      }else{
        alertify.error("Failed an error occured!")
      }
    });
  }
}
