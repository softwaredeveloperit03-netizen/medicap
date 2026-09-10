import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PologComponent } from './polog.component';

describe('PologComponent', () => {
  let component: PologComponent;
  let fixture: ComponentFixture<PologComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PologComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PologComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
