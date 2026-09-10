import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScrapEntryComponent } from './scrap-entry.component';

describe('ScrapEntryComponent', () => {
  let component: ScrapEntryComponent;
  let fixture: ComponentFixture<ScrapEntryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScrapEntryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ScrapEntryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
