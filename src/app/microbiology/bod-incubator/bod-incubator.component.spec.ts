import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BodIncubatorComponent } from './bod-incubator.component';

describe('BodIncubatorComponent', () => {
  let component: BodIncubatorComponent;
  let fixture: ComponentFixture<BodIncubatorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BodIncubatorComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BodIncubatorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
