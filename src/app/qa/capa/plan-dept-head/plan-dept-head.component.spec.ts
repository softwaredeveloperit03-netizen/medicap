import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlanDeptHeadComponent } from './plan-dept-head.component';

describe('PlanDeptHeadComponent', () => {
  let component: PlanDeptHeadComponent;
  let fixture: ComponentFixture<PlanDeptHeadComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlanDeptHeadComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PlanDeptHeadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
