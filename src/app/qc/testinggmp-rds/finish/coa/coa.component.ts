import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-coa',
  templateUrl: './coa.component.html',
  styleUrls: ['./coa.component.css'],
  providers:[DatePipe]
})
export class CoaComponent implements OnInit {

  materials;
  results;
  results1;
  from_date='';
  to_date='';
  material_name='';
  selectedOrder;
  // isView=false;
  isInit=true;
  tests;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getTestings();
    this.getMaterials();
  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    });
  }

  getTestings() {
    this.service.get('qc/testing/raw.php?type=getTestingReport_finish_rds&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
      this.results1 = response;
    });
  }

  viewCOA() {
    // this.service.open('pdf1/coa.php?type=coa&testing_no=' + testing_no);
    this.service.open('qc/testing/raw.php?type=downloadTestingCOAReport&testing_no=' + this.selectedOrder['testing_no']);

  }
  view(index){
    this.selectedOrder = this.results[index];
    this.tests = this.selectedOrder['tests'];
    // this.isView = true;
    this.isInit = false;
  }

  filterStock(){
    this.results = [];
    for(let i=0; i<this.results1.length; i++){
      let data = this.results1[i];
      if(data.material_name!=null){
        if(data.material_name.toUpperCase().includes(this.material_name.toUpperCase())){
          this.results.push(data);
        }
      }
    }
  }

  clear(){
    this.material_name = '';
    this.results = this.results1;
  }
}
