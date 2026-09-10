import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-atr-workflow-list',
  templateUrl: './workflow-list.component.html',
  styleUrls: ['./workflow-list.component.css']
})
export class WorkflowListComponent implements OnInit {
  title = '';
  step = '';
  status = '';
  results: any[] = [];
  loading = false;
  baseRoute = '/qc/analytical-test-request';

  constructor(
    public service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    this.route.data.subscribe(data => {
      this.title = data['title'] || 'Pending Requests';
      this.step = data['step'] || '';
      this.status = data['status'] || '';
      this.loadList();
    });
  }

  loadList() {
    this.loading = true;
    this.service.get('qc/analytical_test_request.php?type=getRequestsByStatus&status=' + encodeURIComponent(this.status))
      .subscribe((res: any) => {
        this.results = Array.isArray(res) ? res : [];
        this.loading = false;
      }, () => { this.loading = false; });
  }

  open(item: any) {
    this.router.navigate([this.baseRoute, this.step, item.id]);
  }

  statusLabel(s: string): string {
    return (s || '').replace(/_/g, ' ').toUpperCase();
  }
}
