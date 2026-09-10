import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ApproveFormulaAAndGenerateMaterialCodeComponent } from './approve-formula-aand-generate-material-code.component';

describe('ApproveFormulaAAndGenerateMaterialCodeComponent', () => {
  let component: ApproveFormulaAAndGenerateMaterialCodeComponent;
  let fixture: ComponentFixture<ApproveFormulaAAndGenerateMaterialCodeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ApproveFormulaAAndGenerateMaterialCodeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ApproveFormulaAAndGenerateMaterialCodeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
