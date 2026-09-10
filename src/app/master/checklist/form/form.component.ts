import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

import {
  buildGroupedChecklistList,
  getEvaluationParameterName,
  GroupedChecklistRow,
} from '../checklist-shared';

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css', '../checklist-table.shared.css'],
})
export class FormComponent implements OnInit {
  isView = false;
  loading = false;
  searchQuery = '';
  checkListData: any[] = [];
  groupedList: GroupedChecklistRow[] = [];
  selectedGroup: GroupedChecklistRow | null = null;
  isuser = 'No';
  loggedInDept: string;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getchecklistnew();
    this.get_rights();
  }

  getEvaluationParameterName = getEvaluationParameterName;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response: any) => {
        this.isuser = response?.[0]?.isuser || 'No';
      });
  }

  getchecklistnew() {
    this.loading = true;
    this.service.get('master/checklist.php?type=getMastercheckList&status=approve').subscribe(
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
}
