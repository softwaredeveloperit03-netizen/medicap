import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  dosages;
  grades;
  dosage_form ='';
  grade='';
  status='';
  selectedResult = [];
  isMrp=false;
  selectedData=[];
  generic_name='';
  product_code='';
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getProductsLog();
    this.getDosages();
    this.getGrades();
  }

  getProductsLog() {
    this.service.get('qa/product.php?type=getProductsLog&dosage_form='+this.dosage_form+'&grade='+ this.grade + '&status='+this.status+'&generic_name='+this.generic_name).subscribe(response => {
      this.results = response;
    });
  }
  getDosages() {
    this.service.get('qa/product.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }
  
  getGrades() {
    this.service.get('qa/product.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  editMrp(index){
    this.selectedData = this.results[index];
    this.isMrp = true;
  }

  edit(index) {
    this.router.navigate(['/product/edit/' + this.results[index].id]);
  }

  open(file) {
    if (file !== '') {
      window.open(this.service.url + 'upload/product/' + file);
    } else {
      alert('File not available');
    }
  }
  
  saveMrp(data){
    if(!data.valid){
       alert('All feilds Are required');
    }
    let temp=data.value;
    temp['product_code']=this.selectedData['product_code'];
    this.service.post('qa/product.php?type=updateMRP',JSON.stringify(temp)).subscribe(response=>{
     if(response['status']=='success'){
       alert('Product Mrp send to approval');
       this.isMrp=false;
     }else{
      alert('not save');
     }
    });
  }
  download(id){
    this.service.open('qa/product.php?type=productmasterpdf&id='+id);
  }

  downloadReport(){
    this.service.open('qa/product.php?type=productmasterlog&dosage_form='+this.dosage_form+'&grade='+ this.grade + '&status='+this.status);
  }

}
