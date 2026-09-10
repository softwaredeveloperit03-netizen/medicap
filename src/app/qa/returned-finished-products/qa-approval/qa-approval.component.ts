import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-returned-products-qa-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['../rfp.shared.css'],
})
export class QaApprovalComponent implements OnInit {
  pendingList: any[] = [];

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadPending();
  }

  loadPending(): void {
    this.service.get('qa/returnedFinishedProducts.php?type=getPendingReturnMerchandiseQa').subscribe((response: any) => {
      this.pendingList = Array.isArray(response) ? response : [];
    });
  }

  review(record: any): void {
    this.router.navigate(['/qa/returned-finished-products/report/qa-review', record.id]);
  }
}
