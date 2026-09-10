import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';
import {
  buildGroupedChecklistList,
  getEvaluationParameterName,
  GroupedChecklistRow,
} from '../checklist-shared';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css', '../checklist-table.shared.css'],
})
export class LogComponent implements OnInit {
  isView = false;
  loading = false;
  checkListData: any[] = [];
  groupedList: GroupedChecklistRow[] = [];
  selectedGroup: GroupedChecklistRow | null = null;
  searchQuery = '';

  constructor(
    private service: DataAccessService,
    public route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.getchecklistnew();
  }

  getEvaluationParameterName = getEvaluationParameterName;

  getchecklistnew(): void {
    this.loading = true;
    this.service
      .get('master/checklist.php?type=getMastercheckList&status=pending')
      .subscribe(
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
      [g.department, g.module, g.form_name, g.entry_by, g.approve_by, g.status]
        .join(' ')
        .toLowerCase()
        .includes(q)
    );
  }

  view(group: GroupedChecklistRow): void {
    this.selectedGroup = group;
    this.isView = true;
  }

  closeView(): void {
    this.isView = false;
    this.selectedGroup = null;
  }
}
