import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ForplanComponent } from './forplan.component';

describe('ForplanComponent', () => {
  let component: ForplanComponent;
  let fixture: ComponentFixture<ForplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ForplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ForplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
