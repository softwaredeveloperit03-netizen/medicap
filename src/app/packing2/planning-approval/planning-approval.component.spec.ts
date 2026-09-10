import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlanningApprovalComponent } from './planning-approval.component';

describe('PlanningApprovalComponent', () => {
  let component: PlanningApprovalComponent;
  let fixture: ComponentFixture<PlanningApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlanningApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PlanningApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
