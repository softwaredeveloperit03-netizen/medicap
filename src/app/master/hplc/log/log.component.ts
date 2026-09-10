import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
   
  results: any[] = [];
  filteredRows: any[] = [];
  searchQuery = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getHPLCLog();
  }

  getHPLCLog(){
    this.service.get('qc/hplc.php?type=getHPLCLog').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.applyFilter();
    });
  }

  download(){
    const q = encodeURIComponent(this.searchQuery || '');
    this.service.open('qc/hplc.php?type=downloadHPLCLogExcel&name=' + q)
  }

  applyFilter() {
    const q = (this.searchQuery || '').toLowerCase().trim();
    this.filteredRows = this.results.filter((row: any) => {
      return (
        !q ||
        (row.column_no || '').toLowerCase().includes(q) ||
        (row.technique || '').toLowerCase().includes(q) ||
        (row.column_name || '').toLowerCase().includes(q) ||
        (row.usp_l_code || '').toLowerCase().includes(q) ||
        (row.pharmacopoeia_reference || '').toLowerCase().includes(q) ||
        (row.status || '').toLowerCase().includes(q)
      );
    });
  }

}
