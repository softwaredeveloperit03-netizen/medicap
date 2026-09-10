import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MediaConsumptionComponent } from './media-consumption.component';

describe('MediaConsumptionComponent', () => {
  let component: MediaConsumptionComponent;
  let fixture: ComponentFixture<MediaConsumptionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MediaConsumptionComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MediaConsumptionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
