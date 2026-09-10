import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeviationApprovalComponent } from './deviation-approval.component';

describe('DeviationApprovalComponent', () => {
  let component: DeviationApprovalComponent;
  let fixture: ComponentFixture<DeviationApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeviationApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeviationApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
