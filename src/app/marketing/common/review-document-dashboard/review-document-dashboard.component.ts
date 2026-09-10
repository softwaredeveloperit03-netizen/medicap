import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-review-document-dashboard',
  templateUrl: './review-document-dashboard.component.html',
  styleUrls: ['./review-document-dashboard.component.css']
})
export class ReviewDocumentDashboardComponent implements OnInit {

  isView = false;
  entries;
  selectedDocument = [];
  remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDocument();
  }

  getPendingDocument() {
    this.service.get('audit-trails.php?type=getPendingDocument').subscribe(response => {
      this.entries = response;
    });
  }

  viewDocument(index) {
    this.selectedDocument = this.entries[index];
    this.isView = true;
  }

  updateReviwDocument(action) {
    this.service.get('audit-trails.php?type=updateReviwDocument&action=' + action + 'remark=' + this.remark).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.remark = '';
      this.getPendingDocument();
    });
  }

}
