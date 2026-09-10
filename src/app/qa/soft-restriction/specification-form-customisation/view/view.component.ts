import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { SpecificationCustomisationLogMeta, SpecificationScope } from '../specification-form-customisation.constants';
import { SpecificationFormCustomisationService } from '../specification-form-customisation.service';

@Component({
  selector: 'app-specification-form-customisation-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.css'],
})
export class ViewComponent implements OnInit {
  scope: SpecificationScope = 'Raw Material';
  record: SpecificationCustomisationLogMeta | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private service: SpecificationFormCustomisationService
  ) {}

  ngOnInit(): void {
    const scopeParam = String(this.route.snapshot.paramMap.get('scope') || '');
    if (scopeParam === 'Packing Material' || scopeParam === 'Finish Product' || scopeParam === 'Raw Material') {
      this.scope = scopeParam;
    }
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.back();
      return;
    }
    this.service.getById(this.scope, id).subscribe((res: any) => {
      this.record = res;
      if (!this.record) {
        this.back();
      }
    }, () => this.back());
  }

  rows(): any[] {
    const f = this.record?.fields;
    return Array.isArray(f) ? f : [];
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/specification-form-customisation']);
  }
}
