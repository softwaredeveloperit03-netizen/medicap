import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { RmMasterCustomisationService } from '../rm-master-customisation.service';
import { RM_CUSTOMISATION_FIELDS, RMCustomisationRecord } from '../rm-master-customisation.constants';

@Component({
  selector: 'app-rm-master-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.css']
})
export class ViewComponent implements OnInit {
  record: RMCustomisationRecord | null = null;
  readonly fields = RM_CUSTOMISATION_FIELDS;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private service: RmMasterCustomisationService
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.params['id'];
    if (!id) {
      this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
      return;
    }
    this.service.getById(+id).subscribe((res: any) => {
      this.record = res && (res.id || res.request_no) ? res : null;
      if (!this.record) {
        this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
      }
    }, () => this.router.navigate(['/qa/soft-restriction/rm-master-customisation']));
  }

  getValue(key: string): string {
    if (!this.record) return '-';
    const v = this.record[key];
    return v !== undefined && v !== null ? String(v) : '-';
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
  }
}
