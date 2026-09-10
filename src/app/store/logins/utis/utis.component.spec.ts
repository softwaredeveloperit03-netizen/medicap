import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UtisComponent } from './utis.component';

describe('UtisComponent', () => {
  let component: UtisComponent;
  let fixture: ComponentFixture<UtisComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UtisComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UtisComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
