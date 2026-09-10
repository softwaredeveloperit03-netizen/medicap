import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]

})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  from_date = '';
  to_date = '';
  selectedResult = [];
  selectedGRN = [];
  issued = [];
  isView1 = false;
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.getMaterials();
  }

  getMaterials() {
    this.service.get('store/bincard.php?type=getMaterials&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  view1(index) {
    let grns = this.selectedResult['grns'];
    this.selectedGRN = grns[index];
    this.issued = this.selectedGRN['issued'];
    this.isView1 = true;
  }

  download() {
    this.service.open('store/bincard.php?type=downloadMaterialLog&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
