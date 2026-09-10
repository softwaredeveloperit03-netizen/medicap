import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  isView = false;
  specifications;
  
  selectedSpec = [];
  materiallist = [];
  material_type = '';
  grade = '';
  status = '';
  maxdate;
  constructor(private service: DataAccessService) { }
  
  ngOnInit() {
    this.getReports();
  }

  getReports() {
    this.service.get('qc/specification/retest.php?type=getSpecificationsLog&material_type='+ this.material_type + '&grade='+ this.grade + '&status='+ this.status).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

}
