<template>
  <div class="container">
    <ol class="breadcrumb">
      <li :class="{active:route.page=='home'}">
        <a href="#home">Overzicht diensten</a>
      </li>
      <li v-if="route.service" :class="{active:route.page=='service'}">
        <a href="#service">Dienst {{ route.service }}</a>
      </li>
      <li v-if="route.channel>-1" :class="{active:route.page=='channel'}">
        <a href="#channel">Kanaal {{ route.channel + 1 }}</a>
      </li>
      <li v-if="route.calendar>-1" :class="{active:route.page=='calendar'}">
        <a href="#calendar">Kalender {{ route.calendar + 1 }}</a>
      </li>
    </ol>
  </div>
</template>
