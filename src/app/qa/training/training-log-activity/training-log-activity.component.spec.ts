import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainingLogActivityComponent } from './training-log-activity.component';

describe('TrainingLogActivityComponent', () => {
  let component: TrainingLogActivityComponent;
  let fixture: ComponentFixture<TrainingLogActivityComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainingLogActivityComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingLogActivityComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
