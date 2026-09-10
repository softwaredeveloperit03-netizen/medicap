import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';

import {
  buildGroupedChecklistList,
  getEvaluationParameterName,
  GroupedChecklistRow,
} from '../checklist-shared';

declare let alertify: any;

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css', '../checklist-table.shared.css'],
})
export class ApproveComponent implements OnInit {
  isView = false;
  loading = false;
  searchQuery = '';
  checkListData: any[] = [];
  groupedList: GroupedChecklistRow[] = [];
  selectedGroup: GroupedChecklistRow | null = null;

  constructor(
    private service: DataAccessService,
    public route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.getchecklistnew();
  }

  getEvaluationParameterName = getEvaluationParameterName;

  getchecklistnew() {
    this.loading = true;
    this.service.get('master/checklist.php?type=getMastercheckList&status=pending').subscribe(
      (response: any) => {
        this.checkListData = Array.isArray(response) ? response : [];
        this.groupedList = buildGroupedChecklistList(this.checkListData);
        this.loading = false;
      },
      () => {
        this.checkListData = [];
        this.groupedList = [];
        this.loading = false;
      }
    );
  }

  get filteredGroups(): GroupedChecklistRow[] {
    if (!this.searchQuery.trim()) {
      return this.groupedList;
    }
    const q = this.searchQuery.toLowerCase().trim();
    return this.groupedList.filter((g) =>
      [g.department, g.module, g.form_name].join(' ').toLowerCase().includes(q)
    );
  }

  view(group: GroupedChecklistRow) {
    this.selectedGroup = group;
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedGroup = null;
  }

  update(action: string) {
    if (!this.selectedGroup) {
      return;
    }
    if (action === 'reject') {
      alertify.warning('Reject is not configured for grouped checklist yet.');
      return;
    }
    const ids = this.selectedGroup.sections.map((s) => s.id).filter(Boolean);
    this.service
      .post('master/checklist.php?type=approve_chklist_group', JSON.stringify({ ids }))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Checklist approved successfully');
          this.getchecklistnew();
          this.closeView();
        } else {
          alertify.error('Failed: please try again');
        }
      });
  }
}
