import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WashwaterComponent } from './washwater.component';

describe('WashwaterComponent', () => {
  let component: WashwaterComponent;
  let fixture: ComponentFixture<WashwaterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WashwaterComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WashwaterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
