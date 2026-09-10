import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-obsolate',
  templateUrl: './obsolate.component.html',
  styleUrls: ['./obsolate.component.css']
})
export class ObsolateComponent implements OnInit {

  isViewSpecification = false;
  specifications;
  selectedSpec = [];
  materiallist = [];
  material_code = '';
  fromdate = '';
  todate = '';
  maxdate;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getReports();
  }
  clearrecords(){
    this.fromdate = '';
    this.material_code = '';
  }
  getReports() {
    this.service.get('qc/specification.php?type=retestobsolate').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isViewSpecification = true;
    this.selectedSpec = this.specifications[index];
  }

}
