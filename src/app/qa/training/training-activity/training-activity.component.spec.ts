import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainingActivityComponent } from './training-activity.component';

describe('TrainingActivityComponent', () => {
  let component: TrainingActivityComponent;
  let fixture: ComponentFixture<TrainingActivityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainingActivityComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingActivityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
