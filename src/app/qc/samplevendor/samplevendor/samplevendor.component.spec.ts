import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SamplevendorComponent } from './samplevendor.component';

describe('SamplevendorComponent', () => {
  let component: SamplevendorComponent;
  let fixture: ComponentFixture<SamplevendorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SamplevendorComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SamplevendorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
