import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlanHeadApprovalComponent } from './plan-head-approval.component';

describe('PlanHeadApprovalComponent', () => {
  let component: PlanHeadApprovalComponent;
  let fixture: ComponentFixture<PlanHeadApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlanHeadApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PlanHeadApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
