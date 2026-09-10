import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainedaidersComponent } from './trainedaiders.component';

describe('TrainedaidersComponent', () => {
  let component: TrainedaidersComponent;
  let fixture: ComponentFixture<TrainedaidersComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainedaidersComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainedaidersComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
