import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WologComponent } from './wolog.component';

describe('WologComponent', () => {
  let component: WologComponent;
  let fixture: ComponentFixture<WologComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WologComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WologComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
