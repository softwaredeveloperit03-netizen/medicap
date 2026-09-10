import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { GmpMaterialFormCustomisationService, GmpFormLogMeta } from '../gmp-material-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-form-layout-view',
  templateUrl: './form-layout-view.component.html',
  styleUrls: ['./form-layout-view.component.css', '../view/view.component.css'],
})
export class FormLayoutViewComponent implements OnInit {
  record: GmpFormLogMeta | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private gmp: GmpMaterialFormCustomisationService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
      return;
    }
    this.gmp.getById(id).subscribe(
      (res) => {
        this.record = res;
        if (!res) {
          alertify.error('Record not found.');
          this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
        }
      },
      () => this.router.navigate(['/qa/soft-restriction/rm-master-customisation'])
    );
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
  }

  fieldRows(): any[] {
    const f = this.record?.fields;
    return Array.isArray(f) ? f : [];
  }
}
