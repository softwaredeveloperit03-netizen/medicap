import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-logs',
  templateUrl: './logs.component.html',
  styleUrls: ['./logs.component.css']
})
export class LogsComponent implements OnInit {

  logs: any[] = [];
  loading = false;
  currentPage = 1;
  pageSize = 50;
  totalRecords = 0;
  searchQuery: string = '';
  fromDate: string = '';
  toDate: string = '';
  barcodeTypeFilter: string = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.toDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    const firstDay = new Date();
    firstDay.setDate(1);
    this.fromDate = this.datePipe.transform(firstDay, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.loadLogs();
  }

  loadLogs() {
    this.loading = true;
    let url = 'store/wms.php?type=getTrackingLogs&page=' + this.currentPage + '&limit=' + this.pageSize;
    
    if (this.fromDate) {
      url += '&from_date=' + encodeURIComponent(this.fromDate);
    }
    if (this.toDate) {
      url += '&to_date=' + encodeURIComponent(this.toDate);
    }
    if (this.barcodeTypeFilter) {
      url += '&barcode_type=' + encodeURIComponent(this.barcodeTypeFilter);
    }
    
    this.service.get(url).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.logs = Array.isArray(response['data']['logs']) ? response['data']['logs'] : [];
          this.totalRecords = response['data']['total'] || 0;
          console.log('Logs:', this.logs);
          console.log('Total Records:', this.totalRecords);
        } else {
          console.log('Error Response:', response);
          alertify.error(response['message'] || 'Failed to load logs');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error loading logs. Please try again.');
        console.error(error);
      }
    );
  }

  loadPage(page: number) {
    this.currentPage = page;
    this.loadLogs();
  }

  applyFilters() {
    this.currentPage = 1;
    this.loadLogs();
  }

  clearFilters() {
    this.searchQuery = '';
    this.barcodeTypeFilter = '';
    this.toDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    const firstDay = new Date();
    firstDay.setDate(1);
    this.fromDate = this.datePipe.transform(firstDay, 'yyyy-MM-dd');
    this.currentPage = 1;
    this.loadLogs();
  }

  get filteredLogs(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.logs;
    }

    const query = this.searchQuery.toLowerCase().trim();
    return this.logs.filter((log) => {
      return Object.entries(log).some(([key, value]) => {
        if (key === 'scanned_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

}
