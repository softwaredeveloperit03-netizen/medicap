import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RiDetectorComponent } from './ri-detector.component';

describe('RiDetectorComponent', () => {
  let component: RiDetectorComponent;
  let fixture: ComponentFixture<RiDetectorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RiDetectorComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RiDetectorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
