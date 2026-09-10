import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  SO_FORM_ID, SO_API, MEDICAP_FROM, SO_STATUS_LABELS, parseSoResponse
} from '../so.constants';
declare let alertify: any;

@Component({
  selector: 'app-so-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.css']
})
export class ViewComponent implements OnInit {
  readonly formId = SO_FORM_ID;
  readonly fromInfo = MEDICAP_FROM;

  id = '';
  loading = true;
  processing = false;
  order: any = null;
  resultsNotes = '';

  constructor(
    public service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    this.id = this.route.snapshot.paramMap.get('id') || '';
    if (this.id) {
      this.loadOrder();
    }
  }

  loadOrder() {
    this.loading = true;
    this.service.get(SO_API + 'type=getOrderById&id=' + this.id).subscribe((res: any) => {
      if (res?.status === 'success' && res.order) {
        this.order = res.order;
        this.resultsNotes = this.order.results_notes || '';
      } else {
        this.order = null;
      }
      this.loading = false;
    }, () => { this.loading = false; });
  }

  statusLabel(s: string): string {
    return SO_STATUS_LABELS[s] || s;
  }

  downloadPdf() {
    if (!this.order?.id) {
      return;
    }
    this.service.open('qc/shipping_order_pdf.php?id=' + this.order.id);
  }

  canCheck(): boolean {
    return this.order?.status === 'pending_check';
  }

  canMarkShipped(): boolean {
    return this.order?.status === 'checked';
  }

  canMarkResultsReceived(): boolean {
    return this.order?.status === 'shipped';
  }

  canReviewResults(): boolean {
    return this.order?.status === 'pending_results_review';
  }

  runAction(type: string, payload: any, successMsg: string) {
    this.processing = true;
    this.service.postTextResponse(SO_API + 'type=' + type, JSON.stringify(payload))
      .subscribe((raw: string) => {
        this.processing = false;
        const res = parseSoResponse(raw);
        if (res?.status === 'success') {
          alertify.success(successMsg);
          this.loadOrder();
        } else {
          alertify.error(res?.message || res?.status || 'Action failed');
        }
      }, () => { this.processing = false; alertify.error('Action failed'); });
  }

  checkOrder() {
    this.runAction('checkOrder', { id: parseInt(this.id, 10) }, 'Checked By sign-off recorded.');
  }

  markShipped() {
    this.runAction('markShipped', {
      id: parseInt(this.id, 10),
      shipping_vendor: this.order?.shipping_vendor || ''
    }, 'Marked as shipped.');
  }

  markResultsReceived() {
    this.runAction('markResultsReceived', { id: parseInt(this.id, 10) }, 'Results received — pending QC review.');
  }

  reviewResults() {
    this.runAction('reviewResults', {
      id: parseInt(this.id, 10),
      results_notes: this.resultsNotes
    }, 'QC results review completed.');
  }
}
