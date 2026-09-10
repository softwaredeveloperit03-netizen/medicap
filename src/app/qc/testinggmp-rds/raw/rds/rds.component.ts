import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rds',
  templateUrl: './rds.component.html',
  styleUrls: ['./rds.component.css'],
  providers:[DatePipe]
})
export class RdsComponent implements OnInit {
  results;
  selectedReport = [];
  from_date='';
  to_date='';
  grades;
  material;
  item;
  material_name='';
  isView = false;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getARReport();
    this.getMaterials();
    this.AllRecord();
  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.material = response;
    });
  }


  getARReport() {
    this.service.get('qc/testing/raw.php?type=getTestingReport&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
      this.item = response;
    });
  }
  getgrade() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    // let url = this.service.url + 'pdf1/rds.php?testing_no=' + this.selectedReport['testing_no'] + '&token=' + localStorage.getItem('token');
    // let url = this.service.url + 'qc/testing/raw.php?type=downloadTestingRDSReport&testing_no=' + this.selectedReport['testing_no'] + '&token=' + localStorage.getItem('token');
    // this.service.open('qc/testing/raw.php?type=downloadTestingRDSReport&testing_no=' + this.selectedReport['testing_no']);
    this.service.open('qc/testing/raw.php?type=downloadTestingReport9&material_name=' + this.material_name);

    // window.open(url, '_blank');
  }

  viewReport(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  download(){
    this.service.open('qc/testing/raw.php?type=downloadTestingReport9&material_name=' + this.material_name);
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      // console.log(material);
      if(material.material_name!=null){

         //console.log(material['material_name'], this.material_name);
        if (material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())) {
          this.item[this.item.length] = material;
        }
      }
    }
 
  }

  downloadPDF(ar_no, type){
    // if(type == 'manual'){
    //   this.service.open('pdf1/testing.php?type=ARReport&ar_no='+ar_no);
    // }else{
    //   this.service.open('pdf1/testing.php?type=ARReportdigital&ar_no='+ar_no);
    // }

    if(type == 'manual'){
      this.service.open('qc/testing/raw.php?type=ARReport&id='+this.selectedReport['id']);
    }else{
      this.service.open('qc/testing/raw.php?type=downloadTestingReportDigital&id='+this.selectedReport['id']);
    }
  }
   
  AllRecord(){
    this.item =this.results;
    this.material_name='';
    
}
}
