import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-obsolate',
  templateUrl: './obsolate.component.html',
  styleUrls: ['./obsolate.component.css']
})
export class ObsolateComponent implements OnInit {
  specifications = [];
  isViewSpecification = false;
  selectedSpec = [];
  materiallist = [];
  material_code = '';
  fromdate = '';
  todate = '';
  maxdate;
  constructor(private service: DataAccessService,private datePipe: DatePipe,) {
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getReports();
    this.getMaterial();
    this.maxdate = new Date().toISOString().slice(0, 10);
  }
  
  clearrecords(){
    this.material_code = '';
    this.fromdate = '';
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.getReports();
  }

  getReports() {
    this.service.get('qc/specification.php?type=stabilityObsolate').subscribe((response:any) => {
      this.specifications = response;
    });
  }

  getMaterial(){
    this.service.get('qc/specification.php?type=stabilityMaterial').subscribe((response:any) => {
      this.materiallist = response;
    });
  }

  viewSpecification(index) {
    this.selectedSpec = this.specifications[index];
    this.isViewSpecification = true;
  }
}
