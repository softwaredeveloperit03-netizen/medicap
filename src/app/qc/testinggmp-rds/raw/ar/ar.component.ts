import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-ar',
  templateUrl: './ar.component.html',
  styleUrls: ['./ar.component.css'],
  providers:[DatePipe]
})
export class ArComponent implements OnInit {
  isView = false;
  samplings: any[] = [];
  from_date='';
  to_date='';
  grades: any[] = [];
  grade = '';
  material: any[] = [];
  results: any[] = [];
  selectedReport: any = {};
  material_name = "";
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getARReport();
    this.getMaterials();
    this.getgrade();
    
  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe((response: any) => {
      this.material = Array.isArray(response) ? response : [];
    });
  }

  getARReport() {
    this.service.get('qc/testing/raw.php?type=getTestingReport&from_date='+this.from_date+'&to_date='+this.to_date).subscribe({
      next: (response) => {
        const rows = Array.isArray(response) ? response : [];
        this.samplings = rows;
        this.results = rows;
      },
      error: () => {
        this.samplings = [];
        this.results = [];
      }
    });
  }
  getgrade() {
    this.service.observableGrade.subscribe((response: any) => {
      this.grades = Array.isArray(response) ? response : [];
    });
  }

  viewReport(index) {
    this.selectedReport = this.samplings[index];
    this.isView = true;
  }
  downloadReport(){
    // this.service.open('pdf1/testing.php?type=ARReportlog');
    this.service.open('qc/testing/raw.php?type=downloadTestingLog&from_date='+this.from_date+'&to_date='+this.to_date);

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

  filterMaterial(){
    const source = Array.isArray(this.results) ? this.results : [];
    const matQ = (this.material_name || '').toUpperCase();
    const gradeQ = (this.grade || '').toUpperCase();
    this.samplings = source.filter((res) => {
      const name = (res && res.material_name ? String(res.material_name) : '').toUpperCase();
      const grade = (res && res.grade ? String(res.grade) : '').toUpperCase();
      const matchName = !matQ || name.includes(matQ);
      const matchGrade = !gradeQ || grade.includes(gradeQ);
      return matchName && matchGrade;
    });
  }

  clear(){
    this.material_name = '';
    this.grade = '';
    this.filterMaterial();
  }

}
