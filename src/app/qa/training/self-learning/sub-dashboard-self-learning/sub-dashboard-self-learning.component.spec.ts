import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SubDashboardSelfLearningComponent } from './sub-dashboard-self-learning.component';

describe('SubDashboardSelfLearningComponent', () => {
  let component: SubDashboardSelfLearningComponent;
  let fixture: ComponentFixture<SubDashboardSelfLearningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SubDashboardSelfLearningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SubDashboardSelfLearningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
